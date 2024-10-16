<?php

namespace Hacp0012\Quest\Commands;

use ConsoleFind;
use Illuminate\Console\Command;

class QuestFind extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "quest:find {keyword}
  {--c|with-comments : Add comments in results}
  {--f|full : Show a full details in results}
  ";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Very helpful to search a reference via a keyword, a class name, a method, a reference or text in php-doc comments';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $finder = new ConsoleFind;

    $results = $finder->search(keyword: $this->argument('keyword'));

    // $this->info("✨ FOUNDED RESULTS FOR " . $this->argument('keyword'));

    $this->printer(data: $results);
  }

  private function printer(array $data)
  {
    // ---
    // green : 1 . namespace\class@method:ref
    //
    // -----------------------------
    // green : 1 . namespace\class@method
    // grey : comments

    if ($this->option('full') != false || $this->option('with-comments') != false) {
      for ($index = 0; $index < count($data); $index++) {

        $item = $data[$index];

        $line = [
          'index' => $index + 1,
          'first_line' => ($this->option('full') ? $item['class_namespace'] . '\\ ' : '') .
            $item['class_name'] . '@' . $item['method'] . ' : [' . $item['ref'] . ']',
          'file' => $item['file_name'] . ':' . $item['line'],
          'comment' => $item['comment'],
        ];

        $this->comment($line['index'] . ' : ' . $line['first_line']);
        if ($this->option('full')) $this->info('     🚧 ' . $line['file']);
        if (($this->option('with-comments') || $this->option('full')) && $line['comment']) $this->line('     ' . $line['comment']);
        $this->newLine();
      }
    } else {
      $items = [];

      for ($index = 0; $index < count($data); $index++) {
        $item = $data[$index];

        $items[] = [
          $index + 1,
          $item['class_namespace'],
          $item['class_name'],
          $item['method'],
          $item['ref'],
        ];
      }

      if (count($items))
        $this->table(['No', 'Namespace', 'Class', 'Method', 'Reference'], $items);
      else $this->error('No result found for : ' . $this->argument('keyword'));
    }
  }
}

include __DIR__ . '/../core/ConsoleFind.php';
