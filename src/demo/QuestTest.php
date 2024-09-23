<?php

namespace App\quest\demo;

use App\Quest\QuestRouter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\Attributs\QuestSpawClass;
use Hacp0012\Quest\Quest;
use Hacp0012\Quest\QuestSpawMethod;

#[QuestSpawClass()]
class QuestTest
{
  /**
   * Create a new class instance of Quest Guru.
   */
  public function __construct() {}

  # -------------------------------------------------------------------------------------------:
  #[QuestSpaw(ref: '9ef4f696-9bdd-4b31-8aba-d626799b2299', method: QuestSpawMethod::GET)]
  public static function printHello(string $message, int|float $age): string
  {
    return "$message ... avec ta tete de Facochere : $age";
  }

  #[QuestSpaw(ref: 'RdVWAQFS7FSFZeYMkp', method: QuestSpawMethod::POST)]
  public function fileTest(array $arr)
  {
    // dd($request);
    return ['OK', $arr];
  }


  # -------------------------------------------------------------------------------------------:
  #[QuestSpaw(ref: 'KsREAZ8dUZDIyzByU9', middleware: 'guest')]
  function myQuestTest(int $start, float $infinityEnd): array
  {
    return [$start, $infinityEnd];
  }

  # -------------------------------------------------------------------------------------------:
  #[QuestSpaw(
    ref: 'my quest flag ID',
    method: QuestSpawMethod::GET,
    middleware: ['sunctum:auth'],
    filePocket: 'guidPicture',
    alias: ['moon' => 'middle'],
    jsonResponse: true,
  )]
  function yogaStage(int $moon, int $sunRise, $guidPicture): int
  {
    // ? Illuminate\Http\UploadedFile ?
    //* -> $guidPicture

    return $moon + $sunRise;
  }
}

class Forest
{
  #[QuestSpaw(ref: 'NAhLlRZW3g3Fbh30dZ')]
  function tree(string $color): int
  {
    return $this->fruits();
  }

  function fruits(): int
  {
    Route::get('/', fn() => view('home'));
    Quest::spawn(uri: 'quest', routes: [Forest::class]);
    route('quest', ['quest_ref' => 'RrOWXRfKOjauvSpc7y', 'count' => 9,]);
    return 18;
  }
}
