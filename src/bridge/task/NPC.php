<?php
declare(strict_types=1);

namespace bridge\task;
use bridge\{Main, Entity\MainEntity};
use pocketmine\scheduler\Task;
use pocketmine\{Server, Player};
use pocketmine\utils\TextFormat;

class NPC extends Task
{

	public function onRun(int $currentTick)
	{
		$level = Server::getInstance()->getDefaultLevel();
		foreach ($level->getEntities() as $entity)
		{
			if ($entity instanceof MainEntity)
			{
				$entity->setNameTag($this->setTag());
				$entity->setNameTagAlwaysVisible(true);
				$entity->setScale(1.5);
			}
		}
	}
	private function getTitle(){
		$title =["§b[Solo]", "§b[Team]", "§b[Squad]"];
		return $title[array_rand($title)];
		}

	private function setTag(): string
	{
		$logo = "§l§eTHE BRIDGE"."\n";
		$title = "§l§bLEFT CLICK TO PLAY"."\n";
		$anak = Main::getInArena();
		$tag = " ";
		$subtitle = "§e $anak players";
		return $logo . $title . $tag . $subtitle;
	}
}
?>