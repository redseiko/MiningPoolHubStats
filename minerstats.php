<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
/**
 * Copyright (C) 2017  James Dimitrov (Jimok82)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *
 */

//DO NOT EDIT THIS FILE! EDIT CONFIG.PHP


require_once("config.php");
require_once("miningpoolhubstats.class.php");


//Check to see we have an API key. Show an error if none is defined.
if ($_GET['api_key'] != null) {
	$api_key = filter_var($_GET['api_key'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}
if ($api_key == null || $api_key == "INSERT_YOUR_API_KEY_HERE" || strlen($api_key) <= 32) {
	die("Please enter an API key: example: " . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?api_key=ENTER_YOUR_KEY_HERE");
}

//Check to see what we are converting to. Default to USD
if ($_GET['fiat'] != null) {
	$fiat = filter_var($_GET['fiat'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}
if ($fiat == "SET_FIAT_CODE_HERE" || strlen($fiat) >= 4) {
	$fiat = "USD";
}

//Check to see what we are converting to. Default to BTC
if ($_GET['crypto'] != null) {
	$crypto = filter_var($_GET['crypto'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}
if ($crypto == "SET_CRYPTO_CODE_HERE" || strlen($crypto) >= 5) {
	$crypto = "ETH";
}

$mph_stats = new miningpoolhubstats($api_key, $fiat, $crypto);
$crypto_decimals = $mph_stats->get_decimal_for_conversion();


//GENERATE THE UI HERE
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Miner Stats</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <style>
        body {
            padding-top: 4.5rem;
        }
    </style>
</head>
<body>
<script language="JavaScript">
    var timerInit = function (cb, time, howOften) {
        // time passed in is seconds, we convert to ms
        var convertedTime = time * 1000;
        var convetedDuration = howOften * 1000;
        var args = arguments;
        var funcs = [];

        for (var i = convertedTime; i > 0; i -= convetedDuration) {
            (function (z) {
                // create our return functions, within a private scope to preserve the loop value
                // with ES6 we can use let i = convertedTime
                funcs.push(setTimeout.bind(this, cb.bind(this, args), z));

            })(i);
        }

        // return a function that gets called on load, or whatever our event is
        return function () {

            //execute all our functions with their corresponsing timeouts
            funcs.forEach(function (f) {
                f();
            });
        };

    };

    // our doer function has no knowledge that its being looped or that it has a timeout
    var doer = function () {
        var el = document.querySelector('#timer');
        var previousValue = Number(el.innerHTML);
        if (previousValue == 1) {
            location.reload();
        } else {
            document.querySelector('#timer').innerHTML = previousValue - 1;
        }
    };


    // call the initial timer function, with the cb, how many iterations we want (30 seconds), and what the duration between iterations is (1 second)
    window.onload = timerInit(doer, 60, 1);
</script>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
    <a class="navbar-brand" href="#">MiningPoolStats</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active"><a class="nav-link" href="#">Stats</a></li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#how_to_use">How To Use</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#changelog">Changelog</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#about_donate">About/Donate</a>
            </li>
        </ul>
        <ul class="nav navbar-nav pull-right">
            <li class="nav-item">
                <a id="timer" class="nav-link">60</a>
            </li>
        </ul>
    </div>
</nav>
<main role="main" class="container">
    <h1>MiningPoolHub Stats</h1>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>Coin</th>
                    <th>Confirmed (% of min payout)</th>
                    <th>Unconfirmed</th>
                    <th>Total</th>
                    <th>Total in <?php echo $mph_stats->crypto; ?></th>
                    <th>Value (Conf.)</th>
                    <th>Value (Unconf.)</th>
                    <th>Value (Total)</th>
                </tr>
				<?php

				foreach ($mph_stats->coin_data as $coin) {
					?>
                    <tr>
                        <td>
                            <a target="_blank" href="https://<?php echo $coin->coin; ?>.miningpoolhub.com/index.php?page=account&action=pooledit"><span
									<?php if ($coin->confirmed >= $mph_stats->all_coins->{$coin->coin}->min_payout * 20) {
										echo 'style="font-weight: bold; color: red;"';
									} else if ($coin->confirmed >= $mph_stats->all_coins->{$coin->coin}->min_payout * 5) {
										echo 'style="font-weight: bold; color: orange;"';
									} else if ($coin->confirmed >= $mph_stats->all_coins->{$coin->coin}->min_payout) {
										echo 'style="font-weight: bold; color: green;"';
									} ?> ><?php echo $coin->coin; ?></span></a></td>
                        <td><?php echo $coin->confirmed; ?><?php echo " (" . number_format(100 * $coin->confirmed / $mph_stats->all_coins->{$coin->coin}->min_payout, 0) . "%)"; ?></td>
                        <td <?php if (array_key_exists($coin->coin, $mph_stats->get_min_payout($coin->coin))) {
							echo 'class="table-info"';
						} ?>><?php echo $coin->unconfirmed; ?></td>
                        <td <?php if (array_key_exists($coin->coin, $mph_stats->get_min_payout($coin->coin))) {
							echo 'class="table-info"';
						} ?>><?php echo number_format($coin->confirmed + $coin->unconfirmed, $crypto_decimals); ?></td>
                        <td <?php if ($coin->unconfirmed_value > 0) {
							echo 'class="table-success"';
						} ?>><?php echo number_format($coin->confirmed_value_c + $coin->unconfirmed_value_c, 8) . " " . $crypto; ?></td>
                        <td <?php if ($coin->confirmed_value > 0) {
							echo 'class="table-success"';
						} ?>><?php echo number_format($coin->confirmed_value, $crypto_decimals) . " " . $fiat; ?></td>
                        <td <?php if ($coin->unconfirmed_value > 0) {
							echo 'class="table-success"';
						} ?>><?php echo number_format($coin->unconfirmed_value, $crypto_decimals) . " " . $fiat; ?></td>
                        <td <?php if ($coin->unconfirmed_value > 0) {
							echo 'class="table-success"';
						} ?>><?php echo number_format($coin->confirmed_value + $coin->unconfirmed_value, $crypto_decimals) . " " . $fiat; ?></td>
                    </tr>
					<?php
				}
				?>
                <tr>
                    <td>TOTAL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><?php echo number_format($mph_stats->confirmed_total_c + $mph_stats->unconfirmed_total_c, 8) . " " . $crypto; ?></td>
                    <td><?php echo number_format($mph_stats->confirmed_total, $crypto_decimals) . " " . $fiat; ?></td>
                    <td><?php echo number_format($mph_stats->unconfirmed_total, $crypto_decimals) . " " . $fiat; ?></td>
                    <td><?php echo number_format($mph_stats->confirmed_total + $mph_stats->unconfirmed_total, $crypto_decimals) . " " . $fiat; ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>#</th>
                    <th>Worker</th>
                    <th>Coin</th>
                    <th>Hashrate</th>
                    <th>Monitor</th>
                </tr>
				<?php $i = 1;
				foreach ($mph_stats->worker_data as $worker) { ?>
                    <tr>
                        <td width=1%><?php echo $i ?></td>
                        <td>
                            <A target="_blank" HREF="https://<?php echo $worker->coin; ?>.miningpoolhub.com/index.php?page=account&action=workers"><?php echo $worker->username; ?></A>
                        </td>
                        <td><?php echo $worker->coin; ?></td>
                        <td><?php echo number_format($worker->hashrate, 2); ?></td>
                        <td><?php echo $worker->monitor == 1 ? "Enabled" : "Disabled"; ?></td>
                    </tr>
					<?php $i++;
				} ?>
            </table>
        </div>
    </div>
</main>
<div class="modal fade" id="about_donate" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">About / How to Donate</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h2>&copy; <?php echo date("Y"); ?> Mindbrite LLC</h2>
                Thank you for your support. If you would like to donate to project to help assist with domain/server/etc. costs, you can do so at the following addresses:
                <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1">BTC</span>
                    <input type="text" class="form-control" value="17ZjS6ZJTCNWrd17kkZpgRHYZJjkq5qT5A" aria-describedby="basic-addon1" disabled>
                </div>
                <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1">LTC</span>
                    <input type="text" class="form-control" value="LdGQgurUKH2J7iBBPcXWyLKUb8uUgXCfFF" aria-describedby="basic-addon1" disabled>
                </div>
                <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1">ETH</span>
                    <input type="text" class="form-control" value="0x6e259a08a1596653cbf66b2ae2c36c46ca123523" aria-describedby="basic-addon1" disabled>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="changelog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Changelog</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h2>Changes 12/12/2017</h2>
                <ul>
                    <li>Added Changelog</li>
                    <li>Changed payout color to three colors (green, orange and red based on percentage of threshold</li>
                </ul>
                <br><br>
                <h4>See <a href="#" data-toggle="modal" data-target="#how_to_use">How To Use</a> for more info.</h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="how_to_use" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">How to Use</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="accordion" role="tablist">
                    <div class="card">
                        <div class="card-header" role="tab" id="headingOne">
                            <h5 class="mb-0">
                                <a class="collapsed" data-toggle="collapse" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How can I view stats in alternate currencies? (USD,GBP,CAD) or in crypto-currenies?
                                </a>
                            </h5>
                        </div>
                        <div id="collapseOne" class="collapse" role="tabpanel" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                MiningPoolStats supports most available currences and cryptocurrencies. If you would like to view an alternate currency, you can by modifying the URL<br>
                                For example:<br>
                                <br><br>
                                For USD:
                                <a href="//<?php echo $_SERVER['HTTP_HOST']; ?>/minerstats.php?fiat=USD&api_key=<?php echo $api_key; ?>"><?php echo $_SERVER['HTTP_HOST']; ?>/minerstats.php?fiat=USD&api_key=<?php echo $api_key; ?></a><br>
                                For GBP:
                                <a href="//<?php echo $_SERVER['HTTP_HOST']; ?>/minerstats.php?fiat=GBP&api_key=<?php echo $api_key; ?>"><?php echo $_SERVER['HTTP_HOST']; ?>/minerstats.php?fiat=GBP&api_key=<?php echo $api_key; ?></a><br>
                                For BTC:
                                <a href="//<?php echo $_SERVER['HTTP_HOST']; ?>/minerstats.php?fiat=BTC&api_key=<?php echo $api_key; ?>"><?php echo $_SERVER['HTTP_HOST']; ?>/minerstats.php?fiat=BTC&api_key=<?php echo $api_key; ?></a><br>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" role="tab" id="headingTwo">
                            <h5 class="mb-0">
                                <a class="collapsed" data-toggle="collapse" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What does it mean when a coin is in green, orange, or red text?
                                </a>
                            </h5>
                        </div>
                        <div id="collapseTwo" class="collapse" role="tabpanel" aria-labelledby="headingTwo" data-parent="#accordion">
                            <div class="card-body">
                                We have implemented some recommended values for coins in order to prevent keeping too much in the pool wallet.<br>
                                <br>
                                <span style="font-weight: bold; color: green;">GREEN</span>: This means that you have reached the minimum payout threshold and you can "cash out" if you want to.<br>
                                <br>
                                <span style="font-weight: bold; color: orange;">ORANGE</span>: This means that you are at 5x the minimum payout and you should consider saving your funds to a local wallet soon.<br>
                                <br>
                                <span style="font-weight: bold; color: red;">RED</span>: This means that you are at 20x the minimum payout and you are probably holding too many coins in an online wallet. You should move coins to a local wallet ASAP.
                                <br>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" role="tab" id="headingThree">
                            <h5 class="mb-0">
                                <a class="collapsed" data-toggle="collapse" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    What is the percentage next to the confirmed value for a coin?
                                </a>
                            </h5>
                        </div>
                        <div id="collapseThree" class="collapse" role="tabpanel" aria-labelledby="headingThree" data-parent="#accordion">
                            <div class="card-body">
                                The percentage next to the coin name indicates how many percent of the minimum payout you have. Once it hits 100% you can "cash out" your coins.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.bundle.min.js" integrity="sha384-3ziFidFTgxJXHMDttyPJKDuTlmxJlwbSkojudK/CkRqKDOmeSbN6KLrGdrBQnT2n" crossorigin="anonymous"></script>
</body>
