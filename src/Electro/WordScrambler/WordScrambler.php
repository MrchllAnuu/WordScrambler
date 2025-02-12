<?php

namespace Electro\WordScrambler;

use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\Listener;
use onebone\economyapi\EconomyAPI;

class WordScrambler extends PluginBase implements Listener{

    public ?string $word = null;
    public float $reward;
    public bool $rewardEnabled = false;
    public array $words = [];
    public function onEnable() : void
    {
        if ($this->getConfig()->get("Reward-Enabled"))
        {
            $this->rewardEnabled = true;
        }
        if (!$this->getServer()->getPluginManager()->getPlugin("EconomyAPI") && $this->rewardEnabled == true)
        {
            $this->getLogger()->warning("Reward has been disabled since you do not have EconomyAPI installed on your server.");
            $this->rewardEnabled = false;
        }
        $this->loadWords();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleDelayedTask(new ScrambleTask($this), (20 * 60 * $this->getConfig()->get("Scramble-Time")));
    }

    public function onChat(playerChatEvent $event)
    {
        $player = $event->getPlayer();
        $msg = $event->getMessage();

        if (strtolower($msg) == strtolower($this->word))
        {
            $event->cancel();
            $this->playerWon($player);
            $this->word = null;
        }
    }


    public function loadWords()
    {
        foreach($this->getConfig()->get("Words") as $word)
        {
            $this->words[] = $word;
        }
    }
    public function playerWon($player)
    {
        $this->getServer()->broadcastMessage("§6" . $player->getName() . " Guessed The Word Correctly.\n§6The Word Was §e" . $this->word);
        if ($this->rewardEnabled)
        {
            EconomyAPI::getInstance()->addMoney($player, $this->reward);
        }
    }

    public function scrambleWord()
    {
        $this->word = $this->words[array_rand($this->words)];
        if ($this->rewardEnabled)
        {
            $this->reward = mt_rand($this->getConfig()->get("Min-Reward"), $this->getConfig()->get("Max-Reward"));
        }
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            if ($this->rewardEnabled)
            {
                $player->sendMessage("§a===== §f[ §2MENYUSUN KATA §f]§a =====\n\n         §fOrang Pertama Yang Menyusun\n         Kata: §f" . str_shuffle($this->word) . "§a\n         Akan Mendapatkan §e$" . $this->reward . "§a!!");
            }
            else
            {
                $player->sendMessage("§bTry to be the first player to unscramble §e". str_shuffle($this->word) . "!");
            }
        }
        $this->getScheduler()->scheduleDelayedTask(new ScrambleTask($this), (20 * 60 * $this->getConfig()->get("Scramble-Time")));
    }
}
