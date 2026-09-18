<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\WritingAiController;
use App\Http\Controllers\VocabularyProgressController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\MatchingGameController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/categories/{category:slug}/{topic:slug}', [TopicController::class, 'index'])->name('topics.index');
Route::get('/writing-ai', [WritingAiController::class, 'index'])->name('writingAi');
Route::post('/writing-ai/evaluate', [WritingAiController::class, 'evaluate'])->name('writingAi.evaluate');

// Minigame Ghép Cặp Từ Vựng
Route::get('/matching-game', [MatchingGameController::class, 'index'])->name('game.matching');
Route::get('/api/game/vocabularies', [MatchingGameController::class, 'getVocabularies'])->name('game.vocabularies');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // API: Tiến độ học từ vựng
    Route::post('/vocabulary/{vocabulary}/review', [VocabularyProgressController::class, 'review'])->name('vocabulary.review');
    Route::post('/vocabulary/{vocabulary}/master', [VocabularyProgressController::class, 'master'])->name('vocabulary.master');
    Route::post('/vocabulary/{vocabulary}/reset', [VocabularyProgressController::class, 'reset'])->name('vocabulary.reset');
    Route::get('/progress/topic/{topicId}', [VocabularyProgressController::class, 'topicProgress'])->name('progress.topic');
});

require __DIR__.'/auth.php';

