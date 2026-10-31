<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\User;
use App\Models\Maker;
use App\Models\CarType;
use App\Models\FuelType;
use App\Models\City;
use App\Models\State;
use App\Models\CarFeatures;
use App\Models\CarImages;
use App\Models\Model;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        $cars = $user->cars()
            ->with(['maker', 'model', 'city', 'carType', 'fuelType', 'primaryImage'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $favIds = $user->favouriteCars()->pluck('car_id')->toArray();

        return view('car.index', ['cars' => $cars, 'favIds' => $favIds]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $makers = Maker::all();
        $carTypes = CarType::all();
        $fuelTypes = FuelType::all();
        $cities = City::all();

        return view('car.create', compact('makers', 'carTypes', 'fuelTypes', 'cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validate Form Data
        $validatedData = $request->validate([
            'maker_id' => 'required|exists:makers,id',
            'model_id' => 'required|exists:models,id',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'car_type_id' => 'required|exists:car_types,id',
            'price' => 'required|integer|min:0',
            'vin' => 'required|string|max:255',
            'mileage' => 'required|integer|min:0',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:45',
            'description' => 'nullable|string',
            'published' => 'nullable|in:on,1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // 2. Create Car Record
        $car = new Car();
        $car->fill($validatedData);

        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        $car->user_id = Auth::id();
        $car->published_at = $request->has('published') ? now() : null;
        $car->save();

        // 3. Create Car Features
        $featuresData = [
            'abs' => $request->has('abs'),
            'air_conditioning' => $request->has('air_conditioning'),
            'power_windows' => $request->has('power_windows'),
            'power_door_locks' => $request->has('power_door_locks'),
            'cruise_control' => $request->has('cruise_control'),
            'bluetooth_connectivity' => $request->has('bluetooth_connectivity'),
            'remote_start' => $request->has('remote_start'),
            'gps_navigation' => $request->has('gps_navigation'),
            'heated_seats' => $request->has('heated_seats'),
            'climate_control' => $request->has('climate_control'),
            'rear_parking_sensors' => $request->has('rear_parking_sensors'),
            'leather_seats' => $request->has('leather_seats'),
        ];

        $features = new CarFeatures($featuresData);
        $features->car_id = $car->id;
        $features->save();


        // 4. Handle Image Uploads
        if ($request->hasFile('images')) {
            try {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store("img/cars/{$car->id}", 'public');

                    CarImages::create([
                        'car_id' => $car->id,
                        'image_path' => "/storage/" . $path,
                        'position' => $index + 1
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Image upload skipped or failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('car.index')
            ->with('success', 'Car created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Car $car)
    {
        $car->load([
            'maker',
            'model',
            'city',
            'carType',
            'fuelType',
            'primaryImage',
            'images',
            'features',
            'owner' => function ($query) {
                $query->withCount('cars');
            },
        ]);

        return view('car.show', ['car' => $car]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Car $car)
    {
        if (!Auth::check() || $car->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $makers = Maker::all();
        $carTypes = CarType::all();
        $fuelTypes = FuelType::all();
        $cities = City::all();

        return view('car.edit', compact('car', 'makers', 'carTypes', 'fuelTypes', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Car $car)
    {
        if (!Auth::check() || $car->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // 1. Validate Form Data
        $validatedData = $request->validate([
            'maker_id' => 'required|exists:makers,id',
            'model_id' => 'required|exists:models,id',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'car_type_id' => 'required|exists:car_types,id',
            'price' => 'required|integer|min:0',
            'vin' => 'required|string|max:255',
            'mileage' => 'required|integer|min:0',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:45',
            'description' => 'nullable|string',
            'published' => 'nullable|in:on,1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // 2. Update Car Record
        $car->fill($validatedData);
        $car->published_at = $request->has('published') ? now() : null;
        $car->save();

        // 3. Update Car Features
        $featuresData = [
            'abs' => $request->has('abs'),
            'air_conditioning' => $request->has('air_conditioning'),
            'power_windows' => $request->has('power_windows'),
            'power_door_locks' => $request->has('power_door_locks'),
            'cruise_control' => $request->has('cruise_control'),
            'bluetooth_connectivity' => $request->has('bluetooth_connectivity'),
            'remote_start' => $request->has('remote_start'),
            'gps_navigation' => $request->has('gps_navigation'),
            'heated_seats' => $request->has('heated_seats'),
            'climate_control' => $request->has('climate_control'),
            'rear_parking_sensors' => $request->has('rear_parking_sensors'),
            'leather_seats' => $request->has('leather_seats'),
        ];

        // Update or Create features
        if ($car->features) {
            $car->features->update($featuresData);
        } else {
            $features = new CarFeatures($featuresData);
            $features->car_id = $car->id;
            $features->save();
        }

        // 4. Handle Image Uploads (Append new images)
        if ($request->hasFile('images')) {
            try {
                $currentMaxPosition = $car->images()->max('position') ?? 0;
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store("img/cars/{$car->id}", 'public');

                    CarImages::create([
                        'car_id' => $car->id,
                        'image_path' => "/storage/" . $path,
                        'position' => $currentMaxPosition + $index + 1
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Image update upload failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('car.index')
            ->with('success', 'Car updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $car)
    {
        if (!Auth::check() || $car->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $car->delete();

        return redirect()->route('car.index')
            ->with('success', 'Car deleted successfully!');
    }

    public function search(Request $request)
    {
        $query = Car::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<', now())
            ->with(['maker', 'model', 'city', 'carType', 'fuelType', 'primaryImage']);

        if ($request->filled('q')) {
            $term = trim($request->q);

            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->whereHas('maker', function ($makerQuery) use ($term) {
                    $makerQuery->where('name', 'like', "%{$term}%");
                })
                    ->orWhereHas('model', function ($modelQuery) use ($term) {
                        $modelQuery->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('city', function ($cityQuery) use ($term) {
                        $cityQuery->where('name', 'like', "%{$term}%");
                    })
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        if ($request->filled('maker_id')) {
            $query->where('maker_id', $request->maker_id);
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('state_id')) {
            $query->whereHas('city', function ($cityQuery) use ($request) {
                $cityQuery->where('state_id', $request->state_id);
            });
        }

        if ($request->filled('car_type_id')) {
            $query->where('car_type_id', $request->car_type_id);
        }

        if ($request->filled('fuel_type_id')) {
            $query->where('fuel_type_id', $request->fuel_type_id);
        }

        if ($request->filled('year_from')) {
            $query->where('year', '>=', $request->year_from);
        }

        if ($request->filled('year_to')) {
            $query->where('year', '<=', $request->year_to);
        }

        if ($request->filled('price_from')) {
            $query->where('price', '>=', $request->price_from);
        }

        if ($request->filled('price_to')) {
            $query->where('price', '<=', $request->price_to);
        }

        if ($request->filled('mileage_from')) {
            $query->where('mileage', '>=', $request->mileage_from);
        }

        if ($request->filled('mileage_to')) {
            $query->where('mileage', '<=', $request->mileage_to);
        }

        if ($request->filled('mileage')) {
            $query->where('mileage', '<=', $request->mileage);
        }

        $sort = $request->get('sort', 'newest');

        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'year_asc':
                $query->orderBy('year', 'asc');
                break;
            case 'year_desc':
                $query->orderBy('year', 'desc');
                break;
            case 'mileage_asc':
                $query->orderBy('mileage', 'asc');
                break;
            case 'mileage_desc':
                $query->orderBy('mileage', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $makers = Maker::all();
        $carTypes = CarType::all();
        $fuelTypes = FuelType::all();
        $states = State::all();
        $cities = City::all();
        $models = Model::all();

        $cars = $query->paginate(15)->appends($request->query());

        $favIds = Auth::check() ? Auth::user()->favouriteCars()->pluck('car_id')->toArray() : [];

        return view('car.search', [
            'cars' => $cars,
            'makers' => $makers,
            'carTypes' => $carTypes,
            'models' => $models,
            'fuelTypes' => $fuelTypes,
            'states' => $states,
            'cities' => $cities,
            'favIds' => $favIds,
            'sort' => $sort,
        ]);
    }

    public function watchlist()
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        $cars = $user
            ->favouriteCars()
            ->with(['maker', 'model', 'city', 'carType', 'fuelType', 'primaryImage'])
            ->paginate(15);

        $favIds = $cars->pluck('id')->toArray();

        return view('car.watchlist', ['cars' => $cars, 'favIds' => $favIds]);
    }

    public function toggleWatchlist(Car $car)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        $toggled = $user->favouriteCars()->toggle($car->id);

        return response()->json([
            'added' => count($toggled['attached']) > 0
        ]);
    }
}