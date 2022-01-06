<template>
  <div class="home">
    <div
      id="contentModal"
      class="fixed z-20 w-screen"
      v-bind:class="{ modalToggle: !isModalToggled }"
    >
      <div class="grid grid-cols-12">
        <div class="hidden lg:block lg:col-span-2"></div>
        <div
          class="col-span-12 lg:col-span-10 h-screen bg-white text-black overflow-y-scroll"
        >
          <div class="grid grid-cols-12">
            <div class="col-span-1"></div>
            <div class="col-span-10">
              <div class="flex items-center h-32 border-b border-black mb-10">
                <div class="flex items-center w-full pt-5">
                  <p class="text-3xl font-medium">1992</p>
                  <div class="flex mx-auto md:ml-24">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6 mr-2 cursor-pointer text-gray-300"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                      />
                    </svg>
                    <span class="hidden md:inline text-gray-300 cursor-pointer"
                      >Previous</span
                    >
                    <span class="mx-6">1 / 4</span>
                    <span class="hidden md:inline cursor-pointer">Next</span>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6 ml-2 cursor-pointer"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                      />
                    </svg>
                  </div>
                  <a @click="toggleModal()" class="flex ml-auto cursor-pointer">
                    Close
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-6 w-6 ml-2"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </a>
                </div>
              </div>
              <div class="grid grid-cols-5 gap-8 mb-20">
                <div class="col-span-5 md:col-span-2">
                  <img src="/hw30img/place.png" />
                  <p class="text-sm mt-2">
                    Exterior view of the gallery on Sonneggstrasse in Zurich,
                    1992
                  </p>
                </div>
                <div class="col-span-5 md:col-span-3">
                  <h1 class="text-5xl font-medium mb-6">
                    Ursula Hauser & Iwan Wirth open their first gallery together
                  </h1>
                  <p class="font-bold mb-6">
                    In 1992, Ursula Hauser and Iwan Wirth open a gallery
                    together in the first-floor apartment of an Art Deco villa
                    on Sonneggstrasse, a quiet, residential street in Zurich,
                    where Iwan Wirth has been living for a year.
                  </p>
                  <p>
                    ‘In the late 1980s I lived in St. Gallen, and as a teenager
                    I ran a gallery there. Beginning in 1985 artists I had only
                    heard of, like Martin Disler, Klaudia Schifferle, Fischli &
                    Weiss, Urs Lüthi, or Josef Felix Müller, were exhibited by
                    curators like Bice Curiger, Bernhard Mendes Bürgi and Harald
                    Szeemann. The important galleries in Switzerland at the time
                    were Thomas Ammann, Pablo Stähli and Stampa in Basel. And of
                    course, Ernst Beyeler! The Zurich institutions in those
                    years that I still remember well are the Shedhalle in the
                    Rote Fabrik and of course the Kunsthalle. I moved to Zurich
                    in 1991 to Sonneggstrasse 84. The art market was stuck in a
                    deep recession, and commercial interest in contemporary art
                    was at a low point. So I lived in that apartment and also
                    ran an art dealership there, but didn’t actually represent
                    any artists in the usual sense’.
                  </p>
                </div>
              </div>
            </div>

            <div class="col-span-1"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- header -->
    <div class="bg-white text-black">
      <div class="grid grid-cols-6 p-4">
        <div>
          <div class="flex">
            <img src="/hw30img/logo.svg" />
          </div>
        </div>

        <div class="col-span-3">
          <div class="flex h-full items-center">
            <img class="ml-8 w-full" src="/hw30img/hwlogo.svg" />
          </div>
        </div>

        <div class="hidden lg:block">
          <div class="flex h-full items-center">
            <ul class="flex">
              <li class="mr-6">
                <a class="hover:underline" href="#">Timeline</a>
              </li>
              <li class="mr-6">
                <a class="hover:underline" href="#">Index</a>
              </li>
              <li><a class="hover:underline" href="#">About</a></li>
            </ul>
          </div>
        </div>

        <div class="hidden lg:block">
          <div class="flex h-full items-center justify-end">
            <ul class="flex">
              <li class="mr-4">ENG</li>
              <li>*!#</li>
            </ul>
          </div>
        </div>

        <div class="ml-auto lg:hidden flex">
          <img class="ml-auto" src="/hw30img/menu.svg" />
        </div>
      </div>
    </div>
    <!-- end header -->

    <!-- timeline text -->
    <div class="container mx-auto grid grid-cols-12 px-5">
      <div
        class="col-span-12 md:col-span-10 lg:col-span-6 md:col-start-2 lg:col-start-4 text-center pt-10 pb-5"
      >
        <h1 class="text-4xl md:text-6xl font-medium mb-4">The timeline</h1>
        <p>
          On the occasion of its 30th anniversary, Hauser & Wirth presents 30
          Years, an interactive, digital chronology that traces the journey of
          Hauser & Wirth and the artists who have shaped it through personal
          stories and a wealth of never-before-seen archival and visual
          material.
        </p>
      </div>
    </div>
    <!-- end timeline-text -->

    <div class="col-span-12 sticky top-0 pt-6 bg-black z-10">
      <ul class="flex md:hidden px-4 h-8">
        <li class="w-1/2 flex">
          1993
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 ml-2"
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
        </li>
        <li class="w-1/2 text-right">Filter</li>
      </ul>
      <ul class="hidden md:flex justify-center">
        <li class="mr-10">
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >View All</a
          >
        </li>
        <li class="mr-10">
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >Key Milestones</a
          >
        </li>
        <li class="mr-10">
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >Historical Exhbitions</a
          >
        </li>
        <li class="mr-10">
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >By Artists & Estates</a
          >
        </li>
        <li>
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >By Locations
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 ml-2"
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
          </a>
        </li>
      </ul>
    </div>

    <!-- timeline -->
    <div
      style="background: linear-gradient(177.84deg, #110223 0%, #73173e 100%)"
    >
      <div class="grid grid-cols-12">
        <div class="hidden md:block md:col-span-2"></div>
        <div class="col-span-12 md:col-span-8">
          <div class="flex flex-col grid grid-cols-9 mx-auto text-white">
            <div class="flex contents">
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-64 w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div class="w-64 top-0 text-center absolute left-4 -ml-32">
                  <div class="pt-12">
                    <p class="text-7xl font-medium mb-2">1993</p>
                    <p class="text-3xl font-medium">The Beginning</p>
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
                    <p class="font-medium text-3xl">
                      Ursula Hauser & Iwan Wirth open their first gallery
                      together
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-8 h-8 absolute top-0 -mt-2 rounded-full bg-white"
                ></div>
              </div>
              <div class="col-start-6 col-end-9">
                <a @click="toggleModal()" class="cursor-pointer"
                  ><img class="mb-20" src="/hw30img/img_2.png"
                /></a>
              </div>
            </div>
            <!-- left -->
            <div class="flex flex-row-reverse contents mt-4">
              <div class="col-start-1 col-end-5 rounded-xl ml-auto">
                <div class="h-full flex items-start">
                  <div class="text-right pl-4">
                    <h3 class="text-sm mb-1">Zurich</h3>
                    <p class="font-medium text-3xl">
                      Ursula Hauser & Iwan Wirth open their first gallery
                      together
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-8 h-8 absolute top-0 -mt-2 rounded-full bg-white"
                ></div>
              </div>
              <div class="col-start-6 col-end-9">
                <a @click="toggleModal()" class="cursor-pointer"
                  ><img class="mb-20" src="/hw30img/img_4.png"
                /></a>
              </div>
            </div>
            <!-- right -->
            <div class="flex contents">
              <div class="col-start-1 col-end-5">
                <a @click="toggleModal()" class="cursor-pointer"
                  ><img class="pl-4 ml-auto mb-10" src="/hw30img/img_3.png" />
                </a>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-8 h-8 absolute top-0 -mt-2 rounded-full bg-white"
                ></div>
              </div>
              <div class="col-start-6 col-end-9 rounded-xl ml-auto">
                <div class="h-full flex items-start">
                  <div class="text-left">
                    <h3 class="text-sm mb-1">Publishers</h3>
                    <p class="font-medium text-3xl">
                      Hauser & Wirth publishes its first books
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <!-- left -->
            <div class="flex flex-row-reverse contents">
              <div class="col-start-1 col-end-5 p-4 rounded-xl my-4 ml-auto">
                <h3 class="mb-1">
                  Discover More <span class="font-semibold">(3)</span>
                </h3>
              </div>
              <div class="col-start-5 col-end-6 mx-auto relative">
                <div class="h-full w-8 flex items-center justify-center">
                  <div
                    class="h-full w-px bg-gray-100 pointer-events-none"
                  ></div>
                </div>
                <div
                  class="w-10 h-10 absolute top-1/2 -mt-5 -ml-1 rounded-full bg-white text-black flex items-center justify-center"
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
      <!-- timeline -->
    </div>
  </div>
</template>

<script>
export default {
  name: "Home",
  components: {},
  data() {
    return {
      isModalToggled: true,
    };
  },
  methods: {
    toggleModal() {
      this.isModalToggled = !this.isModalToggled;
      if (this.isModalToggled) document.body.style.overflow = "scroll";
      if (!this.isModalToggled) document.body.style.overflow = "hidden";
    },
  },
};
</script>

<style>
#contentModal {
  left: 100vw;

  transition: left 0.5s ease;
}
#contentModal.modalToggle {
  left: 0;

  transition: left 0.5s ease;
}
</style>
