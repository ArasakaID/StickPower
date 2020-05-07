<?php

namespace MasApip\StickPower;

use pocketmine\{Server, Player};
use pocketmine\item\{Item, ItemFactory};
use pocketmine\level\Level;
use pocketmine\math\{VoxelRayTrace, Vector3};
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\{PlaySoundPacket, AddActorPacket};
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\player\PlayerInteractEvent;
use jojoe77777\FormAPI;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener {
	
	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label,array $args): bool{
		switch($cmd->getName()){
			case "stickpower":
				if($sender instanceof Player){
					$form = new SimpleForm(function (Player $player, $data){
						$result = $data;
						if($result === null){
							return true;
						}
						switch($result){
							case 0:
								if(!$player->hasPermission("stickpower.lightning")) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§dLightning");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§l§dLightning §7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
							case 1:
								if(!$player->hasPermission("stickpower.teleport")) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§3Teleport");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§l§3Teleport §7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
							case 2:
								if(!$player->hasPermission("stickpower.explode")) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§cExplode");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§l§cExplode §7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
							case 3:
								if(!$player->hasPermission("stickpower.jumpboost")) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§1Jump Boost");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§l§1Jump Boost §7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
						}
					});					
					$form->setTitle("§l§6Stick §aPower");
					$form->setContent("§aKamu akan memiliki §dStick §adengan kekuatan tertentu!");
					if($sender->hasPermission("stickpower.lightning")) $form->addButton("Stick Lightning");
					if($sender->hasPermission("stickpower.teleport")) $form->addButton("Stick Teleport");
					if($sender->hasPermission("stickpower.explode")) $form->addButton("Stick Explode");
					if($sender->hasPermission("stickpower.jumpboost")) $form->addButton("Stick Jump Boost");
					$form->sendToPlayer($sender);
				}
			break;		
		}
		return true;
	}

	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		$block = $event->getBlock();
		if($item->getCustomName() === "§l§aBlink §7(Klik Kanan)"){
			$player->teleport(new Vector3($block->getX(), $block->getY(), $block->getZ()));
		}
		if($item->getCustomName() === "§l§dLightning §7(Klik Kanan)"){
			$pk = new PlaySoundPacket();
			$pk->soundName = "ambient.weather.thunder";
			$pk->volume = 300;
			$pk->pitch = 1;
			$pk->x = $block->getX();
			$pk->y = $block->getY();
			$pk->z = $block->getZ();
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
			$pk = new PlaySoundPacket();
			$pk->soundName = "ambient.weather.lightning.impact";
			$pk->volume = 300;
			$pk->pitch = 1;
			$pk->x = $block->getX();
			$pk->y = $block->getY();
			$pk->z = $block->getZ();
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
			$light = new AddActorPacket();
			$light->type = 93;
			$light->entityRuntimeId = Entity::$entityCount++;
			$light->metadata = array();
			$light->motion = null; 
			$light->yaw = $player->getYaw();
			$light->pitch = $player->getPitch();
			$light->position = new Vector3($block->getX(), $block->getY(), $block->getZ());
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $light);
			foreach($player->getLevel()->getNearbyEntities(new AxisAlignedBB($block->getFloorX() - ($radius = 5), $block->getFloorY() - $radius, $block->getFloorZ() - $radius, $block->getFloorX() + $radius, $block->getFloorY() + $radius, $block->getFloorZ() + $radius), $player) as $e){
				$e->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, 9));
			}
		}	

		if($item->getCustomName() === "§l§3Teleport §7(Klik Kanan)"){
			$start = $player->add(0, $player->getEyeHeight(), 0);
			$end = $start->add($player->getDirectionVector()->multiply($player->getViewDistance() * 16));
			$level = $player->level;

			foreach(VoxelRayTrace::betweenPoints($start, $end) as $vector3){
				if($vector3->y >= Level::Y_MAX or $vector3->y <= 0){
					return;
				}

				if(($result = $level->getBlockAt($vector3->x, $vector3->y, $vector3->z)->calculateIntercept($start, $end)) !== null){
					$target = $result->hitVector;
					$player->teleport($target);
					return;
				}
			}
		}

		if($item->getCustomName() === "§l§cExplode §7(Klik Kanan)"){
			$explosion = new Explosion(new Position($block->getX(), $block->getY(), $block->getZ(), $player->getLevel()), 1, null);
            $explosion->explodeB();
		}

		if($item->getCustomName() === "§l§1Jump Boost §7(Klik Kanan)"){
			$player->setMotion(new Vector3(0, 3, 0));
		}
	}
}
