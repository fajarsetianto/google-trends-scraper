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

Route::get('/', 'AppController@index');

Route::post('/cari', 'GoogleTrendController@find');
Route::post('/search', 'AppController@search')->name('search');
Route::get('/progress/{queue}', 'AppController@progress')->name('progress');
Route::get('/results/{queue}', 'AppController@results')->name('results');
Route::get('/search/suggestion/', 'AppController@getSuggestion')->name('suggestion');


Route::get('/development', 'AppController@debug')->name('debug');


Route::post('/fetch', 'AppController@fetch')->name('fetch');

Route::get('/debug', 'AppController@getSuggestion');

Route::get('/jobs/{queue}','AppController@jobs')->name('queue');