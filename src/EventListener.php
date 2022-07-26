<?php declare(strict_types=1);

namespace famima65536\EconomyCShop;

use famima65536\EconomyCShop\application\exception\DuplicateShopException;
use famima65536\EconomyCShop\application\ShopApplicationService;
use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\model\exception\InvalidProductException;
use famima65536\EconomyCShop\model\Product;
use famima65536\EconomyCShop\model\Shop;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use famima65536\EconomyCShop\utils\economy\EconomyWrapper;
use famima65536\EconomyCShop\utils\economy\TransactionFailureReason;
use famima65536\EconomyCShop\utils\MessageManager;
use famima65536\EconomyCShop\utils\SignParser;
use InvalidArgumentException;
use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\block\utils\SignText;
use pocketmine\block\WallSign;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\ChestPairEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\world\Position;

class EventListener implements Listener{

	public function __construct(
		private ShopApplicationService $shopApplicationService,
		private ShopService $shopService,
		private IShopRepository $shopRepository,
		private EconomyWrapper $economyWrapper,
		private MessageManager $messageManager
	){
	}


	public function onSignChange(SignChangeEvent $event) : void{
		$this->onEndSignEdit($event);
	}

	/**
	 * @notHandler
	 * @param SignChangeEvent $event
	 */
	private function onEndSignEdit(SignChangeEvent $event) : void{
		$signBlock = $event->getSign();
		$player = $event->getPlayer();
		if(!$signBlock instanceof WallSign){
			return;
		}

		$signText = $event->getNewText();
		try{
			$product = SignParser::getInstance()->parse($signText);
		}catch(InvalidProductException $exception){
			$player->sendMessage($this->messageManager->get('create-shop.invalid-product'));
			return;
		}

		if($product === null){
			return;
		}

		assert($product instanceof Product);

		$mainchest = $signBlock->getSide(Facing::opposite($signBlock->getFacing()));
		if(!$mainchest instanceof Chest){
			return;
		}

		$chestPosition = $mainchest->getPosition();

		$tileChest = $chestPosition->getWorld()->getTile($chestPosition);
		assert($tileChest instanceof TileChest);
		$subchest = null;
		if($tileChest->isPaired()){
			$subchestTile = $tileChest->getPair();
			assert($subchestTile instanceof TileChest);
			$subchest = $subchestTile->getBlock();
			assert($subchest instanceof Chest);
		}

		try{
			$this->shopApplicationService->createShop($player, $product, $signBlock, $mainchest, $subchest);
		}catch(DuplicateShopException $exception){
			$player->sendMessage($this->messageManager->get('create-shop.duplicate', []));
			return;
		}
		$shopSignText = new SignText([
			"{$player->getName()}",
			"price:{$product->getPrice()}",
			"amount:{$product->getAmount()}",
			$product->getItem()->getVanillaName()
		]);
		$event->setNewText($shopSignText);
		$player->sendMessage($this->messageManager->get('create-shop.success'));
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if($block instanceof Chest){
			$position = $block->getPosition();
			$world = $position->getWorld()->getFolderName();
			$coordinate = Coordinate::fromPosition($position);
			if(!$this->shopService->existShopChest($world, $coordinate)){
				return;
			}

			$shop = $this->shopRepository->findByChest($world, $coordinate);
			assert($shop instanceof Shop);

			if($shop->getMainChest()->equals($coordinate)){
				$event->cancel();
				$player->sendMessage($this->messageManager->get("break-shop-chest.shop-is-still-open"));
				return;
			}

			if($shop->getOwner() !== $player->getName()){
				$event->cancel();
				$player->sendMessage($this->messageManager->get("break-shop-chest.player-is-not-owner"));
				return;
			}

			$this->shopApplicationService->removeSubChest($shop);
			return;
		}

		if($block instanceof WallSign){

			$position = $block->getPosition();
			$world = $position->getWorld()->getFolderName();
			$coordinate = Coordinate::fromPosition($position);

			$shop = $this->shopRepository->findBySign($world, $coordinate);

			if($shop === null){
				return;
			}

			if($shop->getOwner() !== $player->getName() and !$player->hasPermission('economy-c-shop.force-close-shop')){
				$event->cancel();
				$player->sendMessage($this->messageManager->get("break-shop-sign.player-is-not-owner"));
				return;
			}

			$this->shopApplicationService->destroyShop($shop);
			$player->sendMessage($this->messageManager->get("break-shop-sign.success"));
			return;
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if($block instanceof Chest){
			$position = $block->getPosition();
			$world = $position->getWorld()->getFolderName();
			$coordinate = Coordinate::fromPosition($position);
			if(!$this->shopService->existShopChest($world, $coordinate)){
				return;
			}

			$shop = $this->shopRepository->findByChest($world, $coordinate);
			assert($shop instanceof Shop);
			if($shop->getOwner() !== $player->getName()){
				$event->cancel();
				$player->sendMessage($this->messageManager->get("open-shop-chest.player-is-not-owner"));
				return;
			}
		}

		if($block instanceof WallSign){
			$position = $block->getPosition();
			$world = $position->getWorld()->getFolderName();
			$coordinate = Coordinate::fromPosition($position);

			$shop = $this->shopRepository->findBySign($world, $coordinate);
			if($shop === null){
				return;
			}

			if($shop->getOwner() === $player->getName()){
				$player->sendMessage($this->messageManager->get("buy-item.player-is-owner"));
				return;
			}

			$product = $shop->getProduct();
			if(!$player->getInventory()->canAddItem($product->getItem())){
				$player->sendMessage($this->messageManager->get("buy-item.inventory-full"));
				return;
			}

			$mainchestCoordinate = $shop->getMainChest();
			$tileChest = $block->getPosition()->getWorld()->getTile(new Position($mainchestCoordinate->getX(), $mainchestCoordinate->getY(), $mainchestCoordinate->getZ(), null));
			assert($tileChest instanceof TileChest);
			/** @var Item[] $remain */
			$remain = $tileChest->getInventory()->removeItem($product->getItem());
			if(count($remain) > 0){
				$item = $product->getItem();
				$item->setCount($item->getCount() - $remain[0]->getCount());
				$tileChest->getInventory()->addItem($item);
				$player->sendMessage($this->messageManager->get("buy-item.chest-empty"));
				return;
			}

			$this->economyWrapper->transfer($player->getName(), $shop->getOwner(), $product->getPrice(), 
				onSuccess: function() use($player, $product): void{				
					$player->getInventory()->addItem($product->getItem());
					$player->sendMessage($this->messageManager->get("buy-item.success", [ $product->getItem()->getCustomName(), $product->getItem()->getCount(), $product->getPrice()]));
				}, 
				onFailure: function(TransactionFailureReason $reason) use($tileChest, $product, $player): void{
					$key = match($reason->id()){
						TransactionFailureReason::ACCOUNT_NOT_FOUND()->id() => "buy-item.transaction-fail.acccount-not-found",
						TransactionFailureReason::BALANCE_CAP_EXCEEDED()->id() => "buy-item.transaction-fail.balance-exceeded",
						TransactionFailureReason::BALANCE_INSUFFICIENT()->id() => "buy-item.transaction-fail.balance-insufficient",
						TransactionFailureReason::CAPITAL_PLAYER_OFFLINE()->id() => "buy-item.transaction-fail.capital-player-offline",
						TransactionFailureReason::UNKNOWN()->id() => "buy-item.transaction-fail.unknown",
						default => throw new InvalidArgumentException("invalid reason.")
					};
					$tileChest->getInventory()->addItem($product->getItem());
					$player->sendMessage($this->messageManager->get($key));
					return;
				}
			);
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onChestPair(ChestPairEvent $event) : void{
		foreach([[$event->getLeft(), $event->getRight()], [$event->getRight(), $event->getLeft()]] as [$chest, $pair]){
			$position = $pair->getPosition();
			$shop = $this->shopRepository->findByChest($position->getWorld()->getFolderName(), Coordinate::fromPosition($position));
			if($shop !== null){
				$this->shopApplicationService->addSubChest($shop, $chest);
			}
		}
	}
}
