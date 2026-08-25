<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, VehicleController, CustomerController, RentalController, DashboardController, MasterDataController};
Route::redirect('/', '/login');
Route::get('/login',[AuthController::class,'create'])->name('login');
Route::post('/login',[AuthController::class,'store'])->middleware('throttle:login');
Route::post('/logout',[AuthController::class,'destroy'])->middleware('auth');
Route::middleware('auth')->group(function (): void {
 Route::get('/vehicles',[VehicleController::class,'index']); Route::post('/vehicles',[VehicleController::class,'store']); Route::put('/vehicles/{vehicle}',[VehicleController::class,'update']); Route::post('/vehicles/{vehicle}/archive',[VehicleController::class,'archive']); Route::post('/vehicles/{vehicle}/restore',[VehicleController::class,'restore']);
  Route::get('/master-data', [MasterDataController::class,'index'])->name('master-data.index'); Route::post('/brands',[MasterDataController::class,'brands'])->name('brands.store'); Route::put('/brands/{brand}',[MasterDataController::class,'updateBrand'])->name('brands.update'); Route::get('/types',[MasterDataController::class,'types'])->name('types.by-brand'); Route::post('/types',[MasterDataController::class,'storeType'])->name('types.store'); Route::put('/types/{type}',[MasterDataController::class,'updateType'])->name('types.update');
 Route::get('/customers',[CustomerController::class,'index']); Route::post('/customers',[CustomerController::class,'store']); Route::put('/customers/{customer}',[CustomerController::class,'update']);
 Route::get('/dashboard', DashboardController::class)->name('dashboard');
 Route::get('/rentals', [RentalController::class,'index'])->name('rentals.index'); Route::post('/rentals', [RentalController::class,'store'])->name('rentals.store'); Route::get('/rentals/catalogs', [RentalController::class,'catalogs'])->name('rentals.catalogs'); Route::get('/rentals/availability', [RentalController::class,'availability'])->name('rentals.availability');
 Route::get('/rentals/price-preview', [RentalController::class,'pricePreview'])->name('rentals.price-preview'); Route::get('/rentals/{rental}/history', [RentalController::class,'history'])->name('rentals.history'); Route::get('/rentals/{rental}', [RentalController::class,'show'])->name('rentals.show'); Route::put('/rentals/{rental}', [RentalController::class,'update'])->name('rentals.update'); Route::post('/rentals/{rental}/cancel', [RentalController::class,'cancel'])->name('rentals.cancel'); Route::post('/rentals/{rental}/complete', [RentalController::class,'complete'])->name('rentals.complete');
});
