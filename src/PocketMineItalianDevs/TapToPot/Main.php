<?php

/*
 * PocketMineItalianDevs Plugin
 */

namespace PocketMineItalianDevs\TapToPot;

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
use function array_keys;
use function in_array;

class Main extends PluginBase implements Listener{

	/** @var string */
	private const PREFIX = 'TapToPot : ';

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
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
					$sender->sendMessage(TextFormat::GREEN . self::PREFIX . 'Plugin enabled!');
					$this->getConfig()->set('enable-plugin', true);
					$this->getConfig()->save();
				}elseif($args[0] === 'off'){
					$sender->sendMessage(TextFormat::GOLD . self::PREFIX . 'Plugin disabled!');
					$this->getConfig()->set('enable-plugin', false);
					$this->getConfig()->save();
				}elseif($args[0] === 'reload'){
					$this->reloadConfig();
					$sender->sendMessage(TextFormat::GREEN . self::PREFIX . 'Reloaded succesfuly');
				}else{
					$sender->sendMessage(TextFormat::RED . self::PREFIX . 'Use /ttp <on/off/reload>');
				}
			}
		}
		return true;
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event){
		if(!$this->getConfig()->get('enable-plugin', true) and $event->getPlayer()->hasPermission('ttp.command')) $event->getPlayer()->sendMessage(TextFormat::RED . self::PREFIX . 'The plugin is currently disabled!');
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$meta = $item->getDamage();
		$id = $item->getId();
		if((($id === Item::SPLASH_POTION and $this->getConfig()->get('enable-splash-potion')) or ($id === Item::POTION and $this->getConfig()->get('enable-potion'))) and $this->getConfig()->get('enable-plugin', true)){
			if(in_array($player->getLevel()->getFolderName(), array_keys($this->getConfig()->get('active-on')), true) and in_array($meta, $this->getConfig()->get('active-on')[$player->getLevel()->getFolderName()], true)){
				$effects = Potion::getPotionEffectsById($meta);
				foreach($effects as $effect) $player->addEffect($effect);
				$event->setCancelled(true);
				$player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
				//normal potions sound is client side!
				if($id === Item::SPLASH_POTION) $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_GLASS);
			}
		}
	}
}
