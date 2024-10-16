<?php

namespace Hacp0012\Quest\Commands;

use Illuminate\Console\Command;
use Hacp0012\Quest\core\QuestConsole;

class QuestGenerateId extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'quest:generate-ref
  {length=36 : The length of characters to generate for.}
  {--uuid : Wether to generate UUID enstead to Random string.}
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate QUEST REFERENCE';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $questConsole = new QuestConsole;

    $generatedId = null;

    if ($this->option('uuid')) {
      $generatedId = $questConsole->generateUuid();
    } else {
      $generatedId = $questConsole->generateId(length: $this->argument('length'));
    }

    $this->info("Generated QUEST REFERENCE ✨ " . $generatedId);

    return 0;
  }
}
