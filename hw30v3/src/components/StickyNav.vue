<template>
  <div class="sticky top-0 pt-6 bg-black z-10 text-white">
    <div class="container px-4 mx-auto">
      <ul class="flex lg:hidden h-8">
        <li class="w-1/2 flex">
          ####
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
        <li class="w-1/2 justify-end flex">
          <span class="mr-3">Filter</span>
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
              d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
            />
          </svg>
        </li>
      </ul>
      <ul class="hidden lg:flex justify-center">
        <li class="mx-8">
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >View All</a
          >
        </li>
        <li class="mx-8">
          <a href="#" class="flex h-8 hover:border-b-4 border-white">Artists</a>
          <div class="absolute bg-black -ml-5">
            <ul class="p-5">
              <li>Sub Item Smoething Longer</li>
              <li>Sub Item</li>
              <li>Sub Item</li>
              <li>Sub Item</li>
            </ul>
          </div>
        </li>
        <li class="mx-8">
          <a href="#" class="flex h-8 hover:border-b-4 border-white"
            >Locations</a
          >
        </li>
      </ul>
    </div>
  </div>
</template>
