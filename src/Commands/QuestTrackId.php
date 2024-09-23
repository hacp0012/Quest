<?php

namespace Hacp0012\Quest\Commands;

use Illuminate\Console\Command;
use Hacp0012\Quest\core\QuestConsole;

class QuestTrackId extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'quest:track-ref {ref : The quest REFERENCE generated to spaw a ressource.}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Track Quest Reference.';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $questConsole = new QuestConsole;

    if ($this->argument('ref')) {
      $result = $questConsole->trackId($this->argument('ref'));

      if ($result['status'] == 'UNMATCHED') {
        $this->warn("⚠️   The Ref you provide is not matched.");
        return 0;
      } elseif ($result['status'] == 'NOPE') {
        $this->warn("🚫  Something went wrong.");
        return 0;
      } elseif ($result['status'] == 'EMPTY_PATHS') {
        $this->warn("🛑  The quest paths register is empty.");
        return 0;
      } elseif ($result['status'] == 'MACTHED') {
        $this->comment("🚩  QUEST TRACK REFERENCE : " . $this->argument('ref'));
        $this->line("    ---------------------------------------------");
        $this->newLine();
        $this->line("🗂️   Class Namespace : " . $result['class_namespace']);

        if ($result['attribut'] != null) {
          $this->newLine();
          $attribut = str_replace('{', '[', $result['attribut']);
          $attribut = str_replace('}', ']', $attribut);
          $attribut = str_replace(',', ', ', $attribut);
          $attribut = str_replace(':', ' = ', $attribut);
          $attribut = str_replace('"', '', $attribut);
          $this->comment('🔖   Spaw attribut : ' . $attribut);
        }

        $this->newLine();
        $infos = str_replace('[', '', $result['method_params']);
        $infos = str_replace(']', '', $infos);
        $infos = str_replace('Parameters ', 'Parameters [', $infos);
        $infos = str_replace(' {', '] {', $infos);
        $infos = str_replace('Method ', 'Spaw method 👉 [', $infos);
        $infos = str_replace('Parameter #', 'Parameter 🚧  ', $infos);

        $this->info('✨  ' . $infos);
        // $this->line("-------------------------------------------------");

        return 0;
      }

      $this->error("Something went wrong in query.");

      return 1;
    }

    $this->warn("You must provide a Quest REF.");

    return 1;
  }
}
