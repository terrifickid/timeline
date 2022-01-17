<template>
  <a class="text-white cursor-pointer grid grid-cols-9 flex items-center py-5">
    <div class="col-span-4 text-right">
      <h3 class="text-xs lg:text-base mr-4">
        Discover More
        <span class="font-semibold">(<slot></slot>)</span>
      </h3>
    </div>
    <div class="col-span-1 flex justify-center">
      <div
        class="w-10 h-10 rounded-full bg-white text-black flex items-center justify-center"
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
  </a>
</template>
