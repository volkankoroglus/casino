<?php namespace App\Games\Kernel\Quick;

use App\Currency\Currency;
use App\Events\BalanceModification;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\ModuleSeeder;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ProvablyFairResult;
use App\Models\Leaderboard;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SaveQuickGameResult;

abstract class QuickGame extends Game {

  private string $server_seed;

  abstract function start($user, Data $data);

  abstract function isLoss(ProvablyFairResult $result, Data $data): bool;

  public function process(Data $data) {
    $this->server_seed = (new ModuleSeeder($this, $data->demo(), $data, null))->find(function (ProvablyFairResult $result) use ($data) {
      return $this->isLoss($result, $data);
    });

    //$this->server_seed = ProvablyFair::generateServerSeed();

    $result = $this->start($data->guest() ? null : $data->user(), $data);
    $result_data = $result->toArray($data);

    if (!isset($result_data['code']) && !$data->guest()) {
      $data->user()->balance(Currency::find($data->currency()))->demo($data->demo())->quiet()->subtract($data->bet(), Transaction::builder()->game($this->metadata()->id())->message('Game')->get());

      if ($result->profit() == 0) event(new BalanceModification($data->user(), Currency::find($data->currency()), 'subtract', $data->demo(), $data->bet(), $result->delay));
      else {
        if ($result->multiplier() < 1) event(new BalanceModification($data->user(), Currency::find($data->currency()), 'subtract', $data->demo(), $result->profit(), $result->delay));
        else event(new BalanceModification($data->user(), Currency::find($data->currency()), 'add', $data->demo(), $result->profit() - $data->bet(), $result->delay));
      }

      if (!$data->demo() && $data->user()->vipLevel() > 0 && ($data->user()->weekly_bonus ?? 0) < 100) $data->user()->update(['weekly_bonus' => ($data->user()->weekly_bonus ?? 0) + 0.1]);
    } else return $result_data;

    // TODO push this to laravel job, no need to populate game db prior to return game results
    // if (!$data->demo()) {
    //   $game = \App\Models\Game::create([
    //     'id' => DB::table('games')->count() + 1,
    //     'user' => $data->user()->_id,
    //     'game' => $this->metadata()->id(),
    //     'wager' => $data->bet(),
    //     'multiplier' => $result->multiplier(),
    //     'status' => $result->profit() > 0 ? ($result->multiplier() < 1 ? 'lose' : 'win') : 'lose',
    //     'profit' => $result->profit(),
    //     'server_seed' => $result->seed(),
    //     'client_seed' => $this->client_seed(),
    //     'nonce' => $result->nonce(),
    //     'data' => $result->database_data(),
    //     'type' => 'quick',
    //     'currency' => $data->currency(),
    //     'bet_usd_converted' => Currency::find($data->currency())->convertTokenToFiat($data->bet())
    //   ]);

    //   Leaderboard::insert($game);
    //   self::analytics($game);
    // }
      // if(!$data->demo()) { 
      //   event(new \App\Events\LiveFeedGame($game, $result->delay));
      // }

    SaveQuickGameResult::dispatch($data, $data->user(), $this->metadata()->id(), $result_data, $result->database_data(), $this->client_seed());
    return $result_data;
  }

  public function server_seed($id = null) { // parameter to make ti compatible with Game.php method declaration
    return $this->server_seed;
  }

}
