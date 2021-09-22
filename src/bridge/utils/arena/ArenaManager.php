<?php

namespace bridge\utils\arena;

use pocketmine\level\Position;
use pocketmine\Player;


use pocketmine\math\Vector3;
use pocketmine\math\Vector2;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\DoubleTag;

use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\item\enchantment\Enchantment;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as T;
use Scoreboards\Scoreboards;

use bridge\utils\Team;
use bridge\utils\Utils;
use bridge\Main;

class ArenaManager{
	
	const STAT_ESPERA = 0;
	const STAT_STATING = 1;
	const STAT_START = 2;
	const STAT_RUN = 3;
	
	const STAT_RESTART = 5;
	const STAT_GANHO = 6;
	
	private $players = [];
	
	public $stat = 0;
	
	private $time = 0;
	private $times = 0;
	
	public $plugin;
	private $nametag = [];
	
	public function __construct(Main $plugin, $data){
		$this->plugin = $plugin;
		$this->data = $data;
		$this->initMapa();
		$this->reset(false);
	}
	
	public function getUtils(){
		return (new Utils($this->plugin));
	}
	
	public function getPlugin(){
		return $this->plugin;
	}
	
	public function initMapa(){
		$utils = $this->getUtils();
		$map = $this->data["world"];
		if($utils->backupExists($map)){
			$this->resetMap();
			return true;
		}
		$utils->backupMap($map, $this->plugin->getDataFolder());
	}
	
	public function resetMap(){
		$utils = $this->getUtils();
		$map = $this->data["world"];
		$utils->resetMap($map);
	}
	
	public function getServer(){
		return $this->plugin->getServer();
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function getPlayers(){
		return $this->players;
	}
	
	public function getConfig(){
		return $this->plugin->getConfig()->getAll();
	}
	
	public function getPos1($v = true){
		$data = $this->getData();
		if(isset($data["pos1"])){
			$dt = $data["pos1"];
			if(!$v){
				return new Vector2($dt["x"], $dt["z"]);
			}
			if(isset($data["world"])){
				$name = $data["world"];
				if($this->isLoad($name)){
					$level = $this->getServer()->getLevelByName($name);
					$pos = new Position($dt["x"], $dt["y"], $dt["z"], $level);
					return $pos;
				}
			}
		}
		return null;
	}
	
	public function getPos2($v = true){
		$data = $this->getData();
		if(isset($data["pos2"])){
			$dt = $data["pos2"];
			if(!$v){
				return new Vector2($dt["x"], $dt["z"]);
			}
			if(isset($data["world"])){
				$name = $data["world"];
				if($this->isLoad($name)){
					$level = $this->getServer()->getLevelByName($name);
					$pos = new Position($dt["x"], $dt["y"], $dt["z"], $level);
					return $pos;
				}
			}
		}
		return null;
	}
	
	public function getSpawn1(){
		$data = $this->getData();
		if(isset($data["spawn1"])){
			$dt = $data["spawn1"];
			if(isset($data["world"])){
				$name = $data["world"];
				if($this->isLoad($name)){
					$level = $this->getServer()->getLevelByName($name);
					$pos = new Position($dt["x"], $dt["y"], $dt["z"], $level);
					return $pos;
				}
			}
		}
		return null;
	}
	
	public function getSpawn2(){
		$data = $this->getData();
		if(isset($data["spawn2"])){
			$dt = $data["spawn2"];
			if(isset($data["world"])){
				$name = $data["world"];
				if($this->isLoad($name)){
					$level = $this->getServer()->getLevelByName($name);
					$pos = new Position($dt["x"], $dt["y"], $dt["z"], $level);
					return $pos;
				}
			}
		}
		return null;
	}
	
	public function getRespawn1($v = true){
		$data = $this->getData();
		if(isset($data["respawn1"])){
			$dt = $data["respawn1"];
			if(!$v){
				return new Vector2($dt["x"], $dt["z"]);
			}
			if(isset($data["world"])){
				$name = $data["world"];
				if($this->isLoad($name)){
					$level = $this->getServer()->getLevelByName($name);
					$pos = new Position($dt["x"], $dt["y"], $dt["z"], $level);
					return $pos;
				}
			}
		}
		return null;
	}
	
	public function getRespawn2($v = true){
		$data = $this->getData();
		if(isset($data["respawn2"])){
			$dt = $data["respawn2"];
			if(!$v){
				return new Vector2($dt["x"], $dt["z"]);
			}
			if(isset($data["world"])){
				$name = $data["world"];
				if($this->isLoad($name)){
					$level = $this->getServer()->getLevelByName($name);
					$pos = new Position($dt["x"], $dt["y"], $dt["z"], $level);
					return $pos;
				}
			}
		}
		return null;
	}
	
	public function getLevel(){
		$data = $this->getData();
		if(isset($data["world"])){
			$name = $data["world"];
			if($this->isLoad($name)){
				$level = $this->getServer()->getLevelByName($name);
				return $level;
			}
		}
		return null;
	}
	
	public function isTeamMode(){
		$data = $this->getData();
		if(isset($data["mode"])){
			if($data["mode"] == "team" or $data["mode"] == "squad"){
				return true;
			}
		}
		return false;
	}
	
	public function getSpawn(){
		$data = $this->getData();
		if(isset($data["local-de-espera"])){
			$dt = $data["local-de-espera"];
			if(!isset($dt["level"])){
				return null;
			}
			$pos = new Position($dt["x"], $dt["y"], $dt["z"]);
			if($this->isLoad($dt["level"])){
				$level = $this->getServer()->getLevelByName($dt["level"]);
				$pos->setLevel($level);
			} else {
				$this->broadcast("§c§lErro§r§c No Such Thing As " . $dt["level"] . " Doesn't exist!");
				return null;
			}
			return $pos;
		}
		return null;
	}
	
	public function isInArena($name){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		if(isset($this->players[$name])){
			return true;
		}
		return false;
	}
	
	public function getNecCount(){
		$data = $this->getData();
		if(isset($data["mode"])){
			switch($data["mode"]){
				case "solo":
				return 2;
				case "team":
				return 4;
				case "squad":
				return 8;
			}
		}
		return 2;
	}
	
	public function onRun($timer = null){
		$nec = $this->getNecCount();
		switch($this->stat){
			case self::STAT_ESPERA:
			$players = $this->getPlayers();
			if(count($players) >= $nec){
				$this->stat = self::STAT_STATING;
			} else {
				if(count($players) <= 0){
					return true;
				}
				$level = $this->getLevel();
// subs RolandDev
if(!is_null($level)){
foreach($level->getPlayers() as $p){
$day = date("d");
$month = date("m");
$year = date("Y");
$asu = $p->getLevel()->getFolderName();
$colors = [
           "§l§bTHE§eBRIDGE"];
$andagay =  " " . $colors[array_rand($colors)] . " ";  
$api = Scoreboards::getInstance(); 
$api->new($p, "TheBridge",  $andagay);
$api->setLine($p, 1, "§f$day $month $year");
$api->setLine($p, 2, "          ");
$api->setLine($p, 3, "§fPlayers: §a".count($this->players));
$api->setLine($p, 4, "§fMap: §a$asu");
$api->setLine($p, 5,"   ");
$api->setLine($p, 6, "§fWaiting...");
$api->setLine($p, 7, "     ");
$api->setLine($p, 8, "§fMode: ".T::GREEN.$this->data["mode"]);
$api->setLine($p, 9, "Team: - ");
$api->setLine($p, 10, "    ");
$api->setLine($p, 11, "§earknightpe.hopto.org");
$api->getObjectiveName($p);
			}
		    }
		    }
			break;
			case self::STAT_STATING:
			$players = $this->getPlayers();
			if(count($players) < $nec){
				$this->stat = self::STAT_ESPERA;
				$this->time = 20;
			} else {
				$this->time--;
				$time = $this->time - 6;
				if($time <= 0){
					$this->setTeams();
					$this->stat = self::STAT_START;
					$this->replaceSpawn();
					$this->teleportPlayers($this->getPlayers());
				} else {
					$temp = $this->getTemp($time);
					$level = $this->getLevel();

if(!is_null($level)){
foreach($level->getPlayers() as $p){
$day = date("d");
$month = date("m");
$year = date("Y");
$colors = [
	"§l§bTHE§eBRIDGE"];
 $andagay =  " " . $colors[array_rand($colors)] . " ";           
$asu = $p->getLevel()->getFolderName();
$api = Scoreboards::getInstance();
$p->addTitle("§c$temp Seconds");
$api->new($p, "TheBridge",  $andagay);
$api->setLine($p, 1, "§f$day $month $year");
$api->setLine($p, 2, "          ");
$api->setLine($p, 3, "§fPlayers: §a".count($this->players));
$api->setLine($p, 4, "§fMap: §a$asu");
$api->setLine($p, 5,"   ");
$api->setLine($p, 6, "Starting In §a$temp");
$api->setLine($p, 7, "     ");
$api->setLine($p, 8, "§fMode: ".T::GREEN.$this->data["mode"]);
$api->setLine($p, 9, "Team: - ");
$api->setLine($p, 10, "    ");
$api->setLine($p, 11, "§earknightpe.hopto.org");
$api->getObjectiveName($p);

				}
			}
		    }
		    }
			break;
			case self::STAT_START:
			$this->time--;
			if($this->time <= 0){
				$this->stat = self::STAT_RUN;

			}
			return true;
			break;
			case self::STAT_RUN:
			$level = $this->getLevel();

			//just to make sure it works
			$this->removeY($this->getSpawn1(), true, null, 4);
			$this->removeY($this->getSpawn2(), true, null, 4);

			if(!is_null($level)){
				foreach($level->getPlayers() as $p){
				    //A cool message

//Scoreboard
$day = date("d");
$month = date("m");
$year = date("Y");
$amsu = $this->setRadomTeam($p);
$api = Scoreboards::getInstance();
$p->setGamemode(Player::SURVIVAL);
$asu = $p->getLevel()->getFolderName();
$colors = [
	"§l§bTHE§eBRIDGE"];
$andagay =  " " . $colors[array_rand($colors)] . " ";  
$api->new($p, "TheBridge",  $andagay);
$api->setLine($p, 1, "§f$day $month $year");
$api->setLine($p, 2, "          ");
$api->setLine($p, 3, "§fPlayers: §a".count($this->players));
$api->setLine($p, 4, "§fMap: §a$asu");
$api->setLine($p, 5,"      ");
$api->setLine($p, 6, "§fBlue Team: ".$this->ponts["blue"].T::GRAY."/5");
$api->setLine($p, 7, "§fRed Team: ".$this->ponts["red"].T::GRAY."/5");
$api->setLine($p, 8,"     ");
$api->setLine($p, 9, "§fMode: ".$this->data["mode"]);
$api->setLine($p, 10, "§fTeam: $amsu");
$api->setLine($p, 11, "    ");
$api->setLine($p, 12, "§earknightpe.hopto.org");
$api->getObjectiveName($p);

				}
			}
			
			$this->initPlayers();
			break;
			case self::STAT_RESTART:
			$this->time--;
			if($this->time <= 0){
				$this->stat = self::STAT_RUN;
				$this->broadcast("§l§bSCORE §r\n§7  §1Blue§r§f " . $this->ponts["blue"] . " §7vs §cRed§r§f " . $this->ponts["red"] . "§8\n§8\n§8\n§8\n", 2);
				$this->startGame();
			} else {
				$this->broadcast("§l§6" . $this->time . "§r§8\n§8\n§8\n§8\n§8", 2);
			}
			return true;
			break;
			case self::STAT_GANHO:
			$this->segs--;
			$this->broadcast($this->lastMessage, 2);
			if($this->segs <= 0){
				$players = $this->getPlayers();
				foreach($players as $name => $pl){
					$p = $this->plugin->getServer()->getPlayerExact($name);
					if(is_null($p)){
						unset($this->players[$name]);
						continue;
					}
					$this->quit($p);
				}
				$this->reset(true);
			}
			break;
		}
	}
	
	private $segs = 10;
	
	private function addWin($winner = "blue"){
		$date = $this->getServer()->getPluginManager()->getPlugin("Slapperwin");
		
		$players = $this->getPlayers();
		foreach($players as $name => $pl){
			$team = $this->getTeam($name);
			if($team == $winner){
				if(!is_null($date)){
					$date->addWin($name, "TheBridgeTopWinner");
				}
			}
		}
	}
	
	public function getCount(){
		$count = count($this->players);
		return $count;
	}
	
	private $base = [
	"blue" => 0,
	"red" => 0
	];
	
	private $ponts = [];
	private $team = null;
	
	public function getTeamData(){
		if(is_null($this->team)){
			$nec = $this->getNecCount() / 2;
			$this->team = new Team($nec);
		}
		return $this->team;
	}
	
	private function setRadomTeam($name){
		$data = $this->getTeamData();
		
		if(!$data->isInTeam($name)){
			if($this->setTeam("blue", $name)){
				$this->broadcast("§e$name §fJoined [BLUE] ", 3);
			} elseif($this->setTeam("red", $name)){
				$this->broadcast("§e$name §fJoined [RED]", 3);
			}
			return true;
		}
		return false;
	}
	
	public function setTeam($team = "blue", $name){
		$data = $this->getTeamData();
		
		if($this->getTeam($name) == $team){
			return true;
		}
		if($data->addPlayerTeam($name, $team)){
			return true;
		}
		return false;
	}
	
	private function setTeams(){
		$data = $this->getTeamData();
		$players = $this->getPlayers();
		
		if(count($players) <= 0){
			$this->reset();
			return true;
		}
		foreach($players as $name => $p){
			if(!$data->isInTeam($name)){
				if($this->setTeam("blue", $name)){
					$this->broadcast("$name §bBergabung dengan Tim §bBlue", 3);
				} elseif($this->setTeam("red", $name)){
					$this->broadcast("$name §bBergabung dengan Tim §cRed", 3);
				}
			}
		}
	}
	
	public function getTeam($name){
		$data = $this->getTeamData();
		return $data->getPlayerTeam($name);
	}
	
	public function isTeam($p1, $p2){
		$data = $this->getTeamData();
		if($data->isTeam($p1, $p2)){
			return true;
		}
		return false;
	}
	
	public function reset($value = true){
		$players = $this->getPlayers();
		$this->players = [];
		
		$this->time = 15;
		$this->segs = 10;
		$this->winner = "blue";
		$this->stat = self::STAT_ESPERA;
		
		$this->getTeamData()->reset();
		$this->nametag = [];
		$this->ponts = $this->base;
		
		if($value){
			$this->resetMap();
		}
	}
	
	public function close(){
		$players = $this->getPlayers();
		if(count($players) <= 0){
			$this->reset(true);
			return true;
		}
		foreach($players as $name => $pl){
			$p = $this->plugin->getServer()->getPlayerExact($name);
			if(!is_null($p)){
				$this->quit($p);
			} else {
				unset($this->players[$name]);
			}
		}
		$this->reset(true);
	}
	
	private $lastMessage = "   ";
	
	public function broadcast($message, $type = 1){
		$players = $this->getPlayers();
		if(count($players) <= 0){
			if($message !== 2){
				$this->reset();
			}
			return true;
		}
		foreach($players as $name => $pl){
			$p = $this->plugin->getServer()->getPlayerExact($name);
			if(is_null($p)){
				unset($this->players[$name]);
				continue;
			} elseif(!$this->isInArena($p)){
				unset($this->players[$name]);
				continue;
			}
		
			if($message == 2){
				$team = $this->team[$name] == "blue" ? "§l§1BLUE§r" : "§l§cRED§r";
//Scoreboard                        
				continue;
			}
			$this->lastMessage = $message;
			switch($type){
				case 1:
				$p->sendPopup($message);
				break;
				case 2:
				$p->sendTip($message);
				break;
				case 3:
				$p->sendMessage($message);
				break;
			}
		}
	}
	
	private $custons = [
	"§r§l§9   VELOCISTA§r\n§7(Click para activar)" => "bridge.velocista",
	"§r§l§b  DESTRUCTOR§r\n§7(Click para activar)" => "bridge.destructor",
	"§r§l§c   MATADOR§r\n§7(Click para activar)" => "bridge.matador",
	"§r§l§e     TANK§r\n§7(Click para activar)" => "bridge.tank",
	"§r§l§6  ARQUERO§r\n§7(Click para activar)" => "bridge.arquero"
	];
	
	private $ids = [
	"bridge.velocista" => "373:14",
	"bridge.destructor" => "278:0",
	"bridge.matador" => "276:0",
	"bridge.tank" => "311:0",
	"bridge.arquero" => "261:0"
	];
	
	public function useHab($item, $p){
		$name = strtolower($p->getName());
		$custom = $item->getCustomName();
		
		$inv = $p->getInventory();
		
		if($custom == "§l§c  Volver§r\n§7(Click para volver)§r"){
			$inv->clearAll();
			$inv->setItem(1, Item::get(120, 0, 1)->setCustomName("§r§aVolver al Lobby\n§7 (Presione)"));
		 $inv->setItem(4, Item::get(384, 0, 1)->setCustomName("§r§l§e  HABILIDADES§r\n§7(Click para ver)"));
		 $inv->setItem(7, Item::get(54, 0, 1)->setCustomName("§r§eTeams\n§7(Presione)"));
			return true;
		}
		if($custom == "§r§l§e  HABILIDADES§r\n§7(Click para ver)"){
			$inv->clearAll();
			$slot = 0;
			foreach($this->custons as $name => $perm){
				if($p->hasPermission($perm)){
					if(isset($this->ids[$perm])){
						$dt = explode(":", $this->ids[$perm]);
						$inv->setItem($slot, Item::get($dt[0], $dt[1], 1)->setCustomName($name));
					} else {
						$inv->setItem($slot, Item::get(340, 0, 1)->setCustomName($name));
					}
				} else {
					$inv->setItem($slot, Item::get(351, 1, 1)->setCustomName($name));
				}
				$slot++;
			}
			$inv->setItem(7, Item::get(262, 0, 1)->setCustomName("Back"));
			return true;
		}
		if(isset($this->custons[$custom])){
			$perm = $this->custons[$custom];
			if(!$p->hasPermission($perm)){
				return true;
			}
			$hab = strtolower(str_replace("bridge.", "", $perm));
			
			if(isset($this->skills[$name][0])){
				$hab1 = $this->skills[$name][0];
				if($hab1 == $hab){
					unset($this->skills[$name][0]);
					$p->sendMessage("§c(-) Kemampuan Anda: §f $ hab § telah berhasil dinonaktifkan!");
					return true;
				}
				if($p->hasPermission("bridge.duple.hab")){
					if(isset($this->skills[$name][1])){
						$hab2 = $this->skills[$name][1];
						if($hab2 == $hab){
							unset($this->skills[$name][1]);
							$p->sendMessage("§c(-) Kemampuan Anda: §f $ hab § telah berhasil dinonaktifkan!");
							return true;
						}
					} else {
						$this->skills[$name][0] = $hab;
						$p->sendMessage("§a(+)Kemampuan Anda: §f $ hab §a telah berhasil diaktifkan \n§8 \n§a (++)Anda memiliki dua kemampuan yang diaktifkan: §f $hab1, $hab!");
						return true;
					}
				} else {
					$p->sendMessage("§cAnda hanya dapat memilih satu keterampilan!");
					return true;
				}
			} else {
				$this->skills[$name][0] = $hab;
				$p->sendMessage("§a(+) ¡Tu §f $hab §a telah berhasil diaktifkan!");
				return true;
			}
		}
		return false;
	}
	
	private $skills = [];
	
	public function getHabs($name){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		if(isset($this->skills[$name])){
			return $this->skills[$name];
		}
		return [];
	}
	
	public function hasHab($name, $hab = "velocista"){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		$hab = strtolower($hab);
		
		if(isset($this->skills[$name])){
			$data = $this->skills[$name];
			if(in_array($hab, $data)){
				return true;
			}
		}
		return false;
	}
	
	
	public function initPlayers(){
		$nec = $this->getNecCount() / 2;
		foreach($this->players as $name => $pl){
			$p = $this->plugin->getServer()->getPlayerExact($name);
			if(is_null($p)){
				unset($this->players[$name]);
				continue;
			} elseif(!$this->isInArena($p)){
				unset($this->players[$name]);
				continue;
			}
		}
		
		$count = count($this->players);
		if($count <= 0){
			$this->reset(true);
			return false;
		} elseif($count <= $nec){
			$value = false;
			if($this->isTeamMode()){
				$data = $this->players;
				if($count <= 1){
					$value = true;
				} elseif($this->isTeam(array_shift($data), array_shift($data))){
					$value = true;
				}
			} else {
				$value = true;
			}
			if($value){
				$data = $this->players;
				$team = $this->getTeam(array_shift($data));
				
			
				switch($team){
					case "blue":
					
				}
				$players = $this->getPlayers();
				foreach($players as $name => $pl){
					$p = $this->plugin->getServer()->getPlayerExact($name);
					if(is_null($p)){
						unset($this->players[$name]);
						continue;
					}
					$this->respawnPlayer($p, false);
				}
				//when the games end
				$p->addTitle("§l§7VICTORY!", "§b§lAnda Menang!");
				$this->segs = 10;
				$this->stat = self::STAT_GANHO;
				$this->addWin($team);
				return true;
			}
		}
	}
	
	public function startGame($value = true){
	    $this->removeY($this->getSpawn1(), true, null, 4);
		$this->removeY($this->getSpawn2(), true, null, 4);
	}
	
	public function replaceSpawn($value = true){
		$this->removeY($this->getSpawn1(), false);
		$this->removeY($this->getSpawn2(), false);
	}
	
	public function removeY($pos, $v = true, $dis = null, $ad = 0){
		$level = $this->getLevel();
		if(is_null($dis)){
			$dis = $this->getNecCount();
			$dis = $dis > 4 ? 4 : $dis;
		}
		$yy = $v ? 3 : 5;
		$yy += $ad;
		for($x = $pos->x - $dis; $x <= $pos->x + $dis; $x++){
			for($y = $pos->y + $yy; $y >= $pos->y - 1; $y--){
				for($z = $pos->z + $dis; $z >= $pos->z - $dis; $z--){
					if($v == true){
						$level->setBlock(new Vector3($x, $y, $z), Block::get(0));
					} else {
						$level->setBlock(new Vector3($x, $y, $z), Block::get(20));
					}
				}
			}
		}
		if(!$v){
			$this->removeY($pos->add(0, 1), true, $dis - 1);
		}
	}
	
	public function teleportPlayers($players){
		$level = $this->getLevel();
		
		foreach($players as $name => $pl){
			$p = $this->getServer()->getPlayerExact($name);
			if(is_null($p)){
				unset($this->players[$name]);
				continue;
			}
			
			$named = "§c" . $p->getName();
			$pos = $this->getSpawn2();
			
			$team = $this->getTeam($name);
			
			switch($team){
				case "blue":
				$named = "§1" . $p->getName();
				$pos = $this->getSpawn1();
				break;
				case "red":
				$named = "§c" . $p->getName();
				$pos = $this->getSpawn2();
				break;
			}
			if(!isset($this->nametag[$name])){
				$this->nametag[$name] = $p->getNameTag();
			}
			
			$p->setNameTag($named);
			$this->addItens($p);
			
			$p->teleport($pos);
		}
	}
	
	public function quit(Player $player, $msg = true){
		$name = strtolower($player->getName());
		if(!$this->isInArena($player)){
			return false;
		}
		$player->getInventory()->clearAll();
		$this->getPlugin()->deleteInArena($player);
$player->getArmorInventory()->clearAll();
$player->getCursorInventory()->clearAll();
$player->setFood(20);
$player->setHealth(20);
$player->setGamemode(2);
$api = Scoreboards::getInstance();
$this->getServer()->getCommandMap()->dispatch($player, "sh on");  
$api->remove($player);
//$this->getPlugin()->removeInArena($player);
		$player->removeAllEffects();
		$player->setHealth($player->getMaxHealth());
		$player->setFood(20);
		//Just to make sure it clears
		  $inv = $player->getInventory();
		  $inv->clearAll();
		//Removing the player
		$this->getTeamData()->removePlayerTeam($name);

                        $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                        $player->removeAllEffects();

		
		if(isset($this->nametag[$name])){
			$player->setNameTag($this->nametag[$name]);
			unset($this->nametag[$name]);
		}
		$level = $this->getServer()->getDefaultLevel();
		
		unset($this->players[$name]);
		if($msg){

		}
	}
	
	public function join(Player $player){
		$nec = $this->getNecCount();
		if($this->stat == self::STAT_ESPERA){
			if(count($this->players) >= $nec){
				return false;
			}
		} elseif($this->stat < 2){
			if(count($this->players) >= $nec){
				return false;
			}
			
		} else {
			return false;
		}
		$player->setGamemode(Player::ADVENTURE);
		$player->removeAllEffects();
		$this->getServer()->getCommandMap()->dispatch($player, "sh off"); 
		$player->setHealth($player->getMaxHealth());
		$player->setFood(20);
		$player->setAllowFlight(false);
		$this->getPlugin()->addInArena($player);
		  $inv = $player->getInventory();
		 
		  $inv->clearAll();
		  $inv->setItem(0, Item::get(44, 0, 1));
		 
		 $inv = $player->getInventory();
		 $inv->clearAll();
		 
		 $inv->setItem(8, Item::get(355, 0, 1)->setCustomName("§cBack To Lobby"));
		 
		 
		 $spawn = $this->getSpawn();
		 if(!is_null($spawn)){
		 	$player->teleport($spawn);
		 	$player->setGamemode(Player::ADVENTURE);
		 	$inv->setItem(8, Item::get(355, 0, 1)->setCustomName("§cBack To Lobby"));
		 }
		
		$name = strtolower($player->getName());
		$this->players[$name] = $player;
		
		$this->setRadomTeam($name);
		return true;
	}
	
	
	public function respawnPlayer($p, $v = true){
		$name = strtolower($p->getName());
		$this->addItens($p, $v);
		$p->setGamemode(Player::SURVIVAL);
		
		$team = $this->getTeam($name);
		if(!is_null($team)){
			switch($team){
				case "blue":
				$pos = $this->getRespawn1();
				$p->teleport($pos);
				break;
				case "red":
				$pos = $this->getRespawn2();
				$p->teleport($pos);
				break;
			}
		}
	}
	public function addItens($p, $v = true){
		
		$p->setGamemode(Player::ADVENTURE);
		$p->setHealth($p->getMaxHealth());
		$p->setFood(20);
		
		if(!$v){
			$p->removeAllEffects();
			$inv = $p->getInventory();
			$inv->clearAll();
			
			$inv->setItem(7, Item::get(355, 0, 1)->setCustomName("§r§cTap To Leave\n§7 (Precione)"));
			return true;
		}
		
		$name = strtolower($p->getName());
		$damage = 14;
		
		$team = $this->getTeam($name);
		if(!is_null($team)){
			switch($team){
				case "blue":
				$damage = 11;
				break;
				case "red":
				$damage = 14;
				break;
			}
		}
			
			$inv = $p->getInventory();
		$inv->clearAll();
		
		$esp = Item::get(267, 0, 1);
		if($this->hasHab($p, "matador")){
			$esp = Item::get(276, 0, 1);
		}
		$pic = Item::get(278, 0, 1);
		if($this->hasHab($p, "destruidor")){
			$pic = Item::get(278, 0, 1);
		}
	
		$arco = Item::get(261, 0, 1);
		$flecha = Item::get(262, 0, 8);
		$food = Item::get(364, 0, 5);
		$food2 = Item::get(322, 0, 2);
		$block = Item::get(159, $damage, 64);
		
		$inv->setItem(0, $esp);
		$inv->setItem(1, $pic);
		$p->setGamemode(Player::ADVENTURE);
		$inv->setItem(2, $arco);
		
		$inv->setItem(3, $block);
		$inv->setItem(4, $block);
		$inv->setItem(6, $food2);
		$inv->setItem(7, $food);
		$inv->setItem(32, $flecha);
		
		$cap = Item::get(298, 0, 1);
		
		$peit = Item::get(299, 0, 1);
		if($this->hasHab($p, "tank")){
			$peit = Item::get(311, 0, 1);
		}
		
		$calc = Item::get(300, 0, 1);
		
		$bot = Item::get(301, 0, 1);
		
		$p->getArmorInventory()->setHelmet($cap);
		$p->getArmorInventory()->setChestplate($peit);
		$p->getArmorInventory()->setLeggings($calc);
		$p->getArmorInventory()->setBoots($bot);
	}
	
	public function getPontPos($p, $v = true){
		$name = strtolower($p->getName());
		$team = $this->getTeam($name);
		
		if(!is_null($team)){
			switch($team){
				case "blue":
				if(!$v){
					return $this->getPos1();
				}
				$pos = $this->getPos2();
				return $pos;
				case "red":
				if(!$v){
					return $this->getPos2();
				}
				$pos = $this->getPos1();
				return $pos;
			}
		}
	}
	
	public function addPont($p){
		$name = strtolower($p->getName());
		
		$team = $this->getTeam($name);
		if(!is_null($team)){
			if(isset($this->ponts[$team])){
				if($this->ponts[$team] >= 5){
					return true;
				}
				$this->ponts[$team]++;
			}
			$msg = "§l§bT§eB→§r§f§l " . $p->getName() . " §l§4SCORED§r§6§l " . $this->ponts[$team];
			$msg2 = " §r§4§l✘MERAH §l§4Memenangkan Pertandingan✘";
			switch($team){
				case "blue":
				$p->addTitle($p->getName() ." §l§eSCORED");
				$msg = "§l§bT§eB→§r§f§l " . $p->getName() . " §l§1SCORED§r§6§l " . $this->ponts[$team];
			$msg2 = " §r§l§1✘BLUE §l§4Memenangkan Pertandingan✘";
			}
			$this->broadcast("$msg", 3);
			if($this->ponts[$team] >= 5){
				$players = $this->getPlayers();
				foreach($players as $name => $pl){
					$p = $this->plugin->getServer()->getPlayerExact($name);
					if(is_null($p)){
						unset($this->players[$name]);
						continue;
					}
					$this->respawnPlayer($p, false);
				}
				$this->segs = 10;
				$this->stat = self::STAT_GANHO;
				$p->addTitle("§l§6PERMAINAN TELAH BERAKHIR!");
				$this->addWin($team);
				return true;
			}
		}
		$this->replaceSpawn();
		$this->teleportPlayers($this->getPlayers());
		foreach($this->players as $name => $p){
			if($this->getTeam($p) == $team){
				if($this->hasHab($p, "velocista")){
					$eff = Effect::getEffect(1);
					$eff->setDuration(80*20);
					$eff->setAmplifier(1);
					
					$p->addEffect($eff);
				}
			}
		}
		
		$this->time = 6;
		$this->stat = self::STAT_RESTART;
	}
	
	public function getPont($team = "blue"){
		if(!isset($this->ponts[$team])){
			return 0;
		}
		return $this->ponts[$team];
	}
	
	public function getTemp($time){
		$seg = (int)($time % 60);
		$time /= 60;
		$min = (int)($time % 60);
		$time /= 60;
		$hora = (int)($time % 24);
		if($seg < 10){
			$seg = "0" . $seg;
		}
		if($min < 10){
			$min = "0" . $min;
		}
		if($hora < 10){
			$hora = "0" . $hora;
		}
		return "$min:$seg";
	}
	
	public function isLoad($world){
		if($this->getServer()->isLevelLoaded($world)){
			return true;
		}
		if(!$this->getServer()->isLevelGenerated($world)){
			return false;
		}
		$this->getServer()->loadLevel($world);
		return $this->getServer()->isLevelLoaded($world);
	}
}