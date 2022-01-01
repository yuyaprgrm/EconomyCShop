<?php declare(strict_types=1);

namespace famima65536\EconomyCShop;

use famima65536\EconomyCShop\application\ShopApplicationService;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\repository\JsonShopRepository;
use famima65536\EconomyCShop\repository\SqliteShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use famima65536\EconomyCShop\utils\economy\EconomyAPIWrapper;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SQLite3;
use Webmozart\PathUtil\Path;

class EconomyCShop extends PluginBase{

	private IShopRepository $shopRepository;

	public function onLoad(): void{
		$this->saveDefaultConfig();
		$this->saveResource("message.yml");
	}

	public function onEnable(): void{
		$shopService = new ShopService($this->shopRepository);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(
			new ShopApplicationService($shopService, $this->shopRepository),
			$shopService,
			$this->shopRepository,
			new EconomyAPIWrapper(),
			new Config(Path::join($this->getDataFolder(), "message.yml"), Config::YAML)
		), $this);
	}

	private function setupRepository(){

		$selectedRepository = "";
		switch($this->getConfig()->get("repository")){
			default:
				$this->getLogger()->error("Unknown repository type is selected!");
			case "json":
				$this->shopRepository = new JsonShopRepository(new Config(Path::join($this->getDataFolder(), "shops.json")));
				$selectedRepository = "json";
				break;
//			case "sqlite":
//				$this->shopRepository = new SqliteShopRepository(new SQLite3(Path::join($this->getDataFolder(), "shops.sqlite")));
//				$selectedRepository = "sqlite";
//				break;
		}

		$this->getLogger()->info("${selectedRepository} is selected to save shop data");
	}


	public function onDisable(): void{
		$this->shopRepository->close();
	}
}
