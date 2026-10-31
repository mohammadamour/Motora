<x-app-layout>
    <!-- Home Slider -->
    <section class="hero-slider">
        <!-- Carousel wrapper -->
        <div class="hero-slides">
            <!-- Item 1 -->
            <div class="hero-slide active">
                <div class="container">
                    <div class="slide-content">
                        <h1 class="hero-slider-title">
                            Buy
                            <strong>The Best Cars</strong>
                            <br />
                            in your region
                        </h1>
                        <div class="hero-slider-content">
                            <p>
                                Use powerful search tool to find your desired
                                cars based on multiple search criteria: Maker,
                                Model, Year, Price Range, Car Type, etc...
                            </p>

                            <a href="{{ route('car.search') }}" class="btn btn-hero-slider w-half block text-center">
                                Find the car
                            </a>
                        </div>
                    </div>
                    <div class="slide-image">
                        <img
                            src="{{ asset('img/car-png-39071.png') }}"
                            alt="Featured Car"
                            class="img-responsive"
                        />
                    </div>
                </div>
            </div>
            <!-- Item 2 -->
            <div class="hero-slide">
                <div class="flex container">
                    <div class="slide-content">
                        <h2 class="hero-slider-title">
                            Do you want to
                            <br />
                            <strong>sell your car?</strong>
                        </h2>
                        <div class="hero-slider-content">
                            <p>
                                Submit your car in our user friendly interface,
                                describe it, upload photos and the perfect buyer
                                will find it...
                            </p>

                            <a href="{{ route('car.create') }}" class="btn btn-hero-slider w-half block text-center">
                                Add Your Car
                            </a>
                        </div>
                    </div>
                    <div class="slide-image">
                        <img
                            src="{{ asset('img/car-png-39071.png') }}"
                            alt="Featured Car"
                            class="img-responsive"
                        />
                    </div>
                </div>
            </div>
            <button type="button" class="hero-slide-prev">
                <svg
                    style="width: 18px"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 6 10"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 1 1 5l4 4"
                    />
                </svg>
                <span class="sr-only">Previous</span>
            </button>
            <button type="button" class="hero-slide-next">
                <svg
                    style="width: 18px"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 6 10"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m1 9 4-4-4-4"
                    />
                </svg>
                <span class="sr-only">Next</span>
            </button>
        </div>
    </section>
    <!--/ Home Slider -->

    <main>
        <x-search-form />

        <!-- New Cars -->
        <section>
            <div class="container">
                <h2>Latest Added Cars</h2>
                <div class="car-items-listing">
            @foreach ($cars as $car)
                @php
                    $imagePath = optional($car->primaryImage)->image_path;
                    $imageSrc = ! empty($imagePath) ? $imagePath : "/img/cars/Lexus-RX200t-2016/1.jpeg";
                    $imageAlt = ($car->year ?? "N/A") . " - " . (optional($car->maker)->name ?? "Unknown") . " " . (optional($car->model)->name ?? "Unknown");
                    $location = optional($car->city)->name ?? "Unknown";
                    $title = ($car->year ?? "N/A") . " - " . (optional($car->maker)->name ?? "Unknown") . " " . (optional($car->model)->name ?? "Unknown");
                    $price = '$' . number_format($car->price ?? 0);
                    $badge1 = optional($car->carType)->name ?? null;
                    $badge2 = optional($car->fuelType)->name ?? null;
                    $carLink = route("car.show", $car->id);
                @endphp

                <x-car-item
                    :imageSrc="$imageSrc"
                    :imageAlt="$imageAlt"
                    :location="$location"
                    :title="$title"
                    :price="$price"
                    :badge1="$badge1"
                    :badge2="$badge2"
                    :carLink="$carLink"
                    :carId="$car->id"
                    :isInWatchList="in_array($car->id, $favIds)"
                />
            @endforeach
                </div>
            </div>
        </section>
        <!--/ New Cars -->
    </main>
</x-app-layout>
