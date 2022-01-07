<template>
  <!-- header -->
  <div class="bg-white text-black">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-12 py-4">
        <div class="col-span-2 lg:col-span-1">
          <img class="max-w-full" src="/hw30img/logo.svg" />
        </div>

        <div class="col-span-8 lg:col-span-4 xl:col-span-3">
          <div class="flex h-full items-center">
            <img
              class="mx-auto lg:ml-0 px-8 object-scale-down"
              src="/hw30img/hwlogo.svg"
            />
          </div>
        </div>

        <div class="hidden lg:block lg:col-span-6 xl:col-span-7">
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

        <div class="col-span-2 lg:hidden flex">
          <img class="ml-auto w-8" src="/hw30img/menu.svg" />
        </div>
      </div>
    </div>
  </div>
  <!-- end header -->
</template>
