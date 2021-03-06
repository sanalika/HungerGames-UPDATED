<?php

namespace hungergames\tasks;

use hungergames\Loader;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AsyncMapBackup extends AsyncTask{

        /**
         *
         * @var string $source
         * @var string $destination
         * @var string $game
         *
         */
        private $source, $destination, $game;

        /**
         *
         * AsyncMapBackup constructor.
         *
         * @param string $source
         * @param string $destination
         * @param string $game
         */
        public function __construct(string $source, string $destination, string $game){
                $this->source = $source;
                $this->destination = $destination;
                $this->game = $game;
        }

        /**
         * Actions to execute when run
         *
         * @return void
         */
        public function onRun(){
                $this->delete($this->destination);
                $this->write($this->source, $this->destination);
        }

        /**
         *
         * @param Server $server
         *
         */
        public function onCompletion(Server $server){
                /** @var Loader $HGApi */
                $HGApi = $server->getPluginManager()->getPlugin("HungerGames");
                $mgr = $HGApi->getGlobalManager()->getGameManagerByName($this->game);

                if($mgr !== null){
                        $mgr->game->reloadGameLevel();
                        $mgr->game->reloadSlots();
                        $mgr->refresh();
                        $server->getLogger()->info(TextFormat::GREEN . "Finished copying map of game '" . TextFormat::YELLOW . $mgr->game->getName() . TextFormat::GREEN . "'");
                }
        }

        /**
         *
         * @param $dir
         *
         */
        public function delete($dir){
                if(is_dir($dir)){
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        foreach($files as $file){
                                if($file->isDir()){
                                        rmdir($file->getRealPath());
                                }else{
                                        unlink($file->getRealPath());
                                }
                        }
                        rmdir($dir);
                }
        }

        /**
         *
         * @param $source
         * @param $destination
         *
         */
        public function write($source, $destination){
                $dir = opendir($source);
                @mkdir($destination);
                while(false !== ($file = readdir($dir))){
                        if(($file != '.') && ($file != '..')){
                                if(is_dir($source . '/' . $file)){
                                        $this->write($source . '/' . $file, $destination . '/' . $file);
                                }else{
                                        copy($source . '/' . $file, $destination . '/' . $file);
                                }
                        }
                }
                closedir($dir);
        }
}