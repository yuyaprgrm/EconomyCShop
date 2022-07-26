<?php declare(strict_types=1);

namespace famima65536\EconomyCShop;

use famima65536\EconomyCShop\application\ShopApplicationService;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\repository\JsonShopRepository;
use famima65536\EconomyCShop\repository\SqliteShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use famima65536\EconomyCShop\utils\economy\BedrockEconomyWrapper;
use famima65536\EconomyCShop\utils\economy\EconomyAPIWrapper;
use famima65536\EconomyCShop\utils\MessageManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SQLite3;
use Webmozart\PathUtil\Path;

class EconomyCShop extends PluginBase{

	private IShopRepository $shopRepository;

	public function onLoad(): void{
		$this->saveDefaultConfig();
		$this->saveResource("message.yml");
		$this->setupRepository();
	}

	public function onEnable(): void{
		$shopService = new ShopService($this->shopRepository);
		$nestedMessages = (new Config(Path::join($this->getDataFolder(), "message.yml"), Config::YAML))->getAll();
		$messageManager = new MessageManager(self::flattenArray($nestedMessages), (bool) $this->getConfig()->get("client-side-translation", false));
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(
			new ShopApplicationService($shopService, $this->shopRepository),
			$shopService,
			$this->shopRepository,
			new BedrockEconomyWrapper(),
			$messageManager
		), $this);
	}

	/**
	 * @phpstan-param mixed[] $array nested
	 * @phpstan-return array<string, string>
	 */
	private static function flattenArray(array $array) : array{
		$flattened = [];
		foreach($array as $k => $v){
			if(is_array($v)){
				foreach(self::flattenArray($v) as $k2 => $v2){
					$flattened["$k.$k2"] = $v2;
				}
			}else{
				assert(is_string($v) || is_int($v) || is_float($v) || is_bool($v));
				$flattened[$k] = (string) $v;
			}
		}
		return $flattened;
	}

	private function setupRepository() : void{
		$selectedRepository = "";
		switch($this->getConfig()->get("repository")){
			default:
				$this->getLogger()->error("Unknown repository type is selected!");
			case "json":
				$this->shopRepository = new JsonShopRepository(new Config(Path::join($this->getDataFolder(), "shops.json")));
				$selectedRepository = "json";
				break;
			case "sqlite":
				$this->shopRepository = new SqliteShopRepository(new SQLite3(Path::join($this->getDataFolder(), "shops.sqlite")));
				$selectedRepository = "sqlite";
				break;
		}

		$this->getLogger()->notice("${selectedRepository} is selected to save shop data");
	}


	public function onDisable(): void{
		$this->shopRepository->close();
	}
}
