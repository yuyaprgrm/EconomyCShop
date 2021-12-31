<?php declare(strict_types=1);

namespace famima65536\EconomyCShop;

use famima65536\EconomyCShop\application\exception\DuplicateShopException;
use famima65536\EconomyCShop\application\ShopApplicationService;
use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\model\exception\InvalidProductException;
use famima65536\EconomyCShop\model\Product;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use famima65536\EconomyCShop\utils\economy\EconomyAPIWrapper;
use famima65536\EconomyCShop\utils\economy\EconomyWrapper;
use famima65536\EconomyCShop\utils\SignParser;
use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\block\utils\SignText;
use pocketmine\block\WallSign;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\world\Position;

class EventListener implements Listener{

	public function __construct(
		private EconomyCShop $plugin,
		private ShopApplicationService $shopApplicationService,
		private ShopService $shopService,
		private IShopRepository $shopRepository,
		private EconomyWrapper $economyWrapper
	){
	}


	public function onSignChange(SignChangeEvent $event){
		$oldText = $event->getOldText();
		$newText = $event->getNewText();
		for($i=0; $i<SignText::LINE_COUNT; ++$i){
			if($oldText->getLine($i) !== $newText->getLine($i))
				return;
		}
		$this->onEndSignEdit($event);
	}

	/**
	 * @notHandler
	 * @param SignChangeEvent $event
	 */
	public function onEndSignEdit(SignChangeEvent $event){
		$signBlock = $event->getSign();
		$player = $event->getPlayer();
		if(!$signBlock instanceof WallSign){
			return;
		}

		$signText = $event->getNewText();
		try{
			$product = SignParser::getInstance()->parse($signText);
		}catch(InvalidProductException $exception){
			$player->sendMessage($exception->getMessage());
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
			$subchest = $tileChest->getPair()->getBlock();
			assert($subchest instanceof Chest);
		}

		try{
			$this->shopApplicationService->createShop($player, $product, $signBlock, $mainchest, $subchest);
		}catch(DuplicateShopException $exception){
			$player->sendMessage($exception->getMessage());
			return;
		}
		$shopSignText = new SignText([
			"[§aECS§0]{$player->getName()}",
			"price:{$product->getPrice()}",
			"amount:{$product->getAmount()}",
			$product->getItem()->getVanillaName()
		]);
		$event->setNewText($shopSignText);
		$player->sendMessage("CShop has been created.");
	}

	public function onBlockBreak(BlockBreakEvent $event){
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
			if($shop->getMainChest()->equals($coordinate)){
				$event->cancel();
				$player->sendMessage("You cannot break chest block because chest shop is open.");
				return;
			}

			if($shop->getOwner() !== $player->getName() and !$player->hasPermission('economy-c-shop.force-close-shop')){
				$event->cancel();
				$player->sendMessage("You cannot break chest block because you are not owner.");
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

			if($shop->getOwner() !== $player->getName() and !$player->hasPermission('economy-c-shop.force-close-shop')){
				$event->cancel();
				$player->sendMessage("You cannot break chest block because you are not owner.");
				return;
			}

			$this->shopApplicationService->destroyShop($shop);
			$player->sendMessage("You have closed shop!");
			return;
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
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

			if($shop->getOwner() !== $player->getName()){
				$event->cancel();
				$player->sendMessage("You cannot open chest because you are not owner.");
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
				$player->sendMessage("You cannot buy item because you are owner.");
				return;
			}

			$product = $shop->getProduct();
			if(!$player->getInventory()->canAddItem($product->getItem())){
				$player->sendMessage("You cannot buy item because you do not have enough space in inventory.");
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
				$player->sendMessage("Shop chest is empty! Tell owner to supplement items.");
				return;
			}

			if(!$this->economyWrapper->transfer($player->getName(), $shop->getOwner(), $product->getPrice())){
				$tileChest->getInventory()->addItem($product->getItem());
				$player->sendMessage("You have not enough money to buy product, or owner have too much money to sell.");
				return;
			}

			$player->getInventory()->addItem($product->getItem());
		}
	}

	/**
	 * @priority MONITOR
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$block = $event->getBlock();
		if(!$block instanceof Chest){
			return;
		}

		$position = $block->getPosition();
		$tile = $block->getPosition()->getWorld()->getTile($position);


		if($tile instanceof TileChest){
			foreach(
				[
					Facing::rotateY($block->getFacing(), true),
					Facing::rotateY($block->getFacing(), false)
				] as $side
			){
				$c = $block->getSide($side);
				if($c instanceof Chest and $c->isSameType($block) and $c->getFacing() === $block->getFacing()){
					$pair = $block->getPosition()->getWorld()->getTile($c->getPosition());
					if($pair instanceof TileChest and !$pair->isPaired()){
						$shop = $this->shopRepository->findByChest($position->getWorld()->getFolderName(), Coordinate::fromPosition($c->getPosition()));
						if($shop !== null){
							$this->shopApplicationService->addSubChest($shop, $c);
							break;
						}
					}
				}
			}
		}
	}
}
