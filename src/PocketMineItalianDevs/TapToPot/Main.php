<?php


namespace PocketmineItalianDevs\TapToPot;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

	/** @var bool */
	private $on;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->on = $this->getConfig()->get('enable-plugin');
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($sender instanceof Player and $sender->hasPermission('ttp.command')){
			if($command->getName() === 'ttp'){
				if($args[0] === 'on'){
					$sender->sendMessage(TextFormat::GREEN.'Plugin enabled!');
					$this->on = true;
				}elseif($args[0] === 'off'){
					$sender->sendMessage(TextFormat::GOLD.'Plugin disabled!');
					$this->on = false;
				}elseif($args[0] === 'reload'){
					$this->reloadConfig();
					$sender->sendMessage(TextFormat::GREEN.'Reloaded succesfuly');
				}else{
					$sender->sendMessage(TextFormat::RED . 'Utilizza /ttp <on/off/reload>');
				}
				$this->getConfig()->set('enable-plugin',$this->on);
			}
		}
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event){
		if(!$this->on and $event->getPlayer()->hasPermission('ttp.command')) $event->getPlayer()->sendMessage(TextFormat::RED.'The plugin is currently disabled!');
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$meta = $item->getDamage();
		$id = $item->getId();
		if(($id === Item::SPLASH_POTION and $this->getConfig()->get('enable-splash-potion')) or ($id === Item::POTION and $this->getConfig()->get('enable-potion')) and $this->on){
			foreach($this->getConfig()->get('active-on') as $worlds => $potions){
				if($player->getLevel()->getFolderName() === $worlds){
					foreach($potions as $potion){
						if($meta === $potion){
							$effect = Potion::getPotionEffectsById($meta);
							if(isset($effect[0])){
								$event->setCancelled(true);
								$player->addEffect($effect[0]);
								$player->getInventory()->setItemInHand(Item::get(Item::AIR));
								if($id === Item::SPLASH_POTION){
									$player->getLevel()->broadcastLevelSoundEvent($player,LevelSoundEventPacket::SOUND_GLASS);
								}
								else{
									//$player->getLevel()->broadcastLevelSoundEvent($player,LevelSoundEventPacket::SOUND_DRINK);
									// What's the name of the sound when you drink a potion? if you know that open an issue and tell the sound name, Thanks!
								}
							}
							break;
						}
					}
					break;
				}
			}
		}
	}
}