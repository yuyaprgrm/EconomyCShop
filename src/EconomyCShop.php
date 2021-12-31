<?php declare(strict_types=1);

namespace famima65536\EconomyCShop;

use famima65536\EconomyCShop\application\ShopApplicationService;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\repository\JsonShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use famima65536\EconomyCShop\utils\economy\EconomyAPIWrapper;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Webmozart\PathUtil\Path;

class EconomyCShop extends PluginBase{

	private IShopRepository $shopRepository;

	public function onLoad(): void{
		$shopDataConfig = new Config(Path::join($this->getDataFolder(), "shops.json"), Config::JSON);
		$this->shopRepository = new JsonShopRepository($shopDataConfig);
	}

	public function onEnable(): void{
		$shopService = new ShopService($this->shopRepository);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(
			$this,
			new ShopApplicationService($shopService, $this->shopRepository),
			$shopService,
			$this->shopRepository,
			new EconomyAPIWrapper()
		), $this);
	}


	public function onDisable(): void{
		$this->shopRepository->close();
	}
}
