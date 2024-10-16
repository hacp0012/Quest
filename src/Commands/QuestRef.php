<?php

namespace Hacp0012\Quest\Commands;

use ConsoleRef;
use Illuminate\Console\Command;

class QuestRef extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "quest:ref
  {--l|list : Print a list of all references.}
  {--e|no-table : Dont print results in a table.}
  {--i|index= : Show only items of the specified index's. Separate index by comma --index=1,2,4}
  {--g|generate= : Generate a random refernce.}
  {--u|g-uuid : Generate a unique uuid refernce.}
  {--t|track= : Track a reference.}
  ";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Reference consosle manager';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    if ($this->option('list')) {
      $ref = new ConsoleRef;

      $list = $ref->getList();

      // dd($list);
      // Filter index.
      $indexList = $this->option('index');
      if ($indexList) {
        $indexList = explode(',', $indexList);

        $newList = [];

        foreach ($indexList as $index) {
          $newList[] = $list[intval($index - 1)];
        }

        $list = $newList;
      }

      $this->printer($list);
    } elseif ($this->option('track')) {
      $this->call('quest:track-ref', ['ref' => $this->option('track')]);
    } elseif ($this->option('generate')) {
      $this->call('quest:generate-ref', ['length' => $this->option('generate') ?? 36]);
    } elseif ($this->option('g-uuid')) {
      $this->call('quest:generate-ref', ['--uuid' => true]);
    } else $this->error("No action : check your option");
  }

  private function printer(array $data)
  {
    // ---
    // green : 1 . namespace\class@method:ref
    //
    // -----------------------------
    // green : 1 . namespace\class@method
    // grey : comments

    if ($this->option('no-table')) {
      for ($index = 0; $index < count($data); $index++) {

        $item = $data[$index];

        $line = [
          'index' => $index + 1,
          'first_line' => $item['class_namespace'] . '\\ ' . $item['class_name'] . '@' . $item['method'] . ' : [' . $item['ref'] . ']',
          'file' => $item['file_name'] . ':' . $item['line'],
          'comment' => $item['comment'],
        ];

        $this->comment($line['index'] . ' : ' . $line['first_line']);
        $this->info('     🚧 ' . $line['file']);
        // $this->line('     ' . $line['comment']);
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

include __DIR__ . '/../core/ConsoleRef.php';
