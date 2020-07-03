<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Services\Trend;

Route::get('/', 'AppController@index')->name('home');

Route::get('/1',function(){
    return view('pages.new');
});

Route::post('/cari', 'GoogleTrendController@find');
Route::post('/search', 'AppController@search')->name('search');
Route::get('/progress/{queue}', 'AppController@progress')->name('progress');
Route::get('/results/{queue}', 'AppController@results')->name('results');
Route::get('/search/suggestion/', 'AppController@getSuggestion')->name('suggestion');


Route::get('/development', 'AppController@debug')->name('debug');


Route::post('/fetch', 'AppController@fetch')->name('fetch');

Route::get('/debug', function(){
    $trend = new Trend();
    $trend->multiline('dbd', 0,'2012-01-01 2016-12-01');
});

Route::get('/jobs/{queue}','AppController@jobs')->name('queue');