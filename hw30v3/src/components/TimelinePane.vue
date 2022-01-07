<template>
  <!-- timeline -->
  <div style="background: linear-gradient(177.84deg, #110223 0%, #73173e 100%)">
    <div class="container px-4 mx-auto">
      <div class="grid grid-cols-12">
        <div class="hidden md:block md:col-span-2 pt-10">
          <div class="sticky top-24 text-white">
            <div class="mx-auto">
              <div
                class="mx-auto flex border rounded-full border-white h-7 w-7 items-center justify-center"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 15l7-7 7 7"
                  />
                </svg>
              </div>
              <div class="mb-4 w-6 mx-auto">
                <div
                  class="h-10 mx-auto w-px border-l border-white border-dashed"
                ></div>
              </div>
              <ul class="w-10 mx-auto">
                <li class="my-4 text-sm">2000s</li>
                <li class="my-4 text-sm">2010s</li>
                <li class="my-4 text-sm">2020s</li>
              </ul>
              <div class="mt-4 w-6 mx-auto">
                <div
                  class="h-10 mx-auto w-px border-l border-white border-dashed"
                ></div>
              </div>
              <div class="flex">
                <div
                  class="mx-auto flex border rounded-full border-white h-7 w-7 items-center justify-center"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 9l-7 7-7-7"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-8">
          <div class="flex flex-col grid grid-cols-9 mx-auto text-white">
            <div class="flex contents">
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-48 lg:h-64 w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div class="w-64 top-0 text-center absolute left-4 -ml-32">
                  <div class="pt-12">
                    <p class="text-5xl lg:text-7xl font-medium mb-2">1993</p>
                    <p class="text-2xl font-medium">The Beginning</p>
                  </div>
                </div>
              </div>
            </div>
            <!-- left -->
            <div class="flex flex-row-reverse contents">
              <div class="col-start-1 col-end-5 rounded-xl ml-auto">
                <div class="h-full flex items-start">
                  <div class="text-right ml-4">
                    <h3 class="text-sm mb-1">Zurich</h3>
                    <p class="font-medium text-xl lg:text-3xl mb-10">
                      Ursula Hauser & Iwan Wirth open their first gallery
                      together
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-6 lg:w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-6 h-6 lg:w-8 lg:h-8 absolute top-0 rounded-full bg-white"
                ></div>
              </div>
              <div class="col-start-6 col-end-9">
                <a @click="toggleModal()" class="cursor-pointer"
                  ><img class="mb-10 lg:mb-20" src="/hw30img/img_2.png"
                /></a>
              </div>
            </div>
            <!-- left -->
            <div class="flex flex-row-reverse contents mt-4">
              <div class="col-start-1 col-end-5 rounded-xl ml-auto">
                <div class="h-full flex items-start">
                  <div class="text-right pl-4">
                    <h3 class="text-sm mb-1">Zurich</h3>
                    <p class="font-medium text-xl lg:text-3xl mb-10">
                      Ursula Hauser & Iwan Wirth open their first gallery
                      together
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-6 lg:w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-6 h-6 lg:w-8 lg:h-8 absolute top-0 rounded-full bg-white"
                ></div>
              </div>
              <div class="col-start-6 col-end-9">
                <a @click="toggleModal()" class="cursor-pointer"
                  ><img class="mb-10 lg:mb-20" src="/hw30img/img_4.png"
                /></a>
              </div>
            </div>
            <!-- right -->
            <div class="flex contents">
              <div class="col-start-1 col-end-5">
                <a @click="toggleModal()" class="cursor-pointer"
                  ><img
                    class="pl-4 ml-auto mb-10 lg:mb-20"
                    src="/hw30img/img_3.png"
                  />
                </a>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-6 lg:w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-6 h-6 lg:w-8 lg:h-8 absolute top-0 rounded-full bg-white"
                ></div>
              </div>
              <div class="col-start-6 col-end-9 rounded-xl ml-auto">
                <div class="h-full flex items-start">
                  <div class="text-left">
                    <h3 class="text-sm mb-1">Publishers</h3>
                    <p class="font-medium text-xl lg:text-3xl">
                      Hauser & Wirth publishes its first books
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <!-- left -->
            <div class="flex flex-row-reverse contents">
              <div class="col-start-1 col-end-5 p-4 rounded-xl my-4 ml-auto">
                <h3 class="mb-1 text-xs lg:text-base">
                  Discover More <span class="font-semibold">(3)</span>
                </h3>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-6 lg:w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-10 h-10 absolute top-1/2 -mt-5 -ml-2 lg:-ml-1 rounded-full bg-white text-black flex items-center justify-center"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- timeline -->
</template>
