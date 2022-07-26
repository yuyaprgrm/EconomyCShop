<?php declare(strict_types=1);

namespace famima65536\EconomyCShop;

use famima65536\EconomyCShop\application\ShopApplicationService;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\repository\JsonShopRepository;
use famima65536\EconomyCShop\repository\SqliteShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use famima65536\EconomyCShop\utils\economy\BedrockEconomyWrapper;
use famima65536\EconomyCShop\utils\economy\CapitalWrapper;
use famima65536\EconomyCShop\utils\economy\EconomyWrapper;
use famima65536\EconomyCShop\utils\MessageManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SQLite3;
use Webmozart\PathUtil\Path;

class EconomyCShop extends PluginBase{

	const CURRENT_CONFIG_VERSION = 2;

	private IShopRepository $shopRepository;

	public function onLoad(): void{
		$this->saveDefaultConfig();
		$this->saveResource("message.yml");
		$this->setupRepository();
	}

	public function onEnable(): void{
		if(!$this->checkIfConfigVersionIsLatest()){
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		};
		$economyWrapper = $this->selectEconomyWrapper();
		if($economyWrapper === null){
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$shopService = new ShopService($this->shopRepository);
		$nestedMessages = (new Config(Path::join($this->getDataFolder(), "message.yml"), Config::YAML))->getAll();
		$messageManager = new MessageManager(self::flattenArray($nestedMessages), (bool) $this->getConfig()->get("client-side-translation", false));
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(
			new ShopApplicationService($shopService, $this->shopRepository),
			$shopService,
			$this->shopRepository,
			$economyWrapper,
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

	private function selectEconomyWrapper() : ?EconomyWrapper{
		$plugin = $this->getConfig()->get('economy-plugin');
		assert(is_string($plugin));
		if($plugin !== 'BedrockEconomy' && $plugin !== 'Capital'){
			$this->getLogger()->critical('economy-plugin in config.yml should be BedrockEconomy or Capital.');
			return null;
		}
		if(!$this->getServer()->getPluginManager()->getPlugin($plugin)?->isEnabled()){
			$this->getLogger()->critical("Plugin $plugin is not detected.");
			return null;
		}
		return match($plugin){
			'BedrockEconomy' => new BedrockEconomyWrapper(),
			'Capital' => new CapitalWrapper($this->getServer(), $this->getConfig()->getNested('capital-option.selector'))
		};
	}

	private function checkIfConfigVersionIsLatest() : bool{
		if($this->getConfig()->get('version', 0) !== self::CURRENT_CONFIG_VERSION){
			$this->getLogger()->critical('config is outdated. please move/rename/remove config.yml and regenerate one.');
			return false;
		}
		return true;
	}


	public function onDisable(): void{
		$this->shopRepository->close();
	}
}
