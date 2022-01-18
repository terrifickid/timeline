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
          <a
            @click="viewAll()"
            class="flex h-8 hover:border-b-4 border-white cursor-pointer"
            >View All</a
          >
        </li>
        <li class="mx-8">
          <a
            @click="toggleNav('artists')"
            class="flex h-8 hover:border-b-4 border-white cursor-pointer"
            >Artists</a
          >
          <div v-show="nav == 'artists'" class="absolute bg-black -ml-5">
            <ul class="p-5">
              <li v-for="(artist, index) in artists" :key="index" class="mb-1">
                <a
                  @click="setArtistFilter(artist)"
                  :class="{
                    'font-bold': artistsFilter.includes(artist.slug),
                  }"
                  class="cursor-pointer"
                  >{{ artist.name }}</a
                >
              </li>
            </ul>
          </div>
        </li>
        <li class="mx-8">
          <a
            @click="toggleNav('locations')"
            class="flex h-8 hover:border-b-4 border-white cursor-pointer"
            >Locations</a
          >
          <div v-show="nav == 'locations'" class="absolute bg-black -ml-5">
            <ul class="p-5">
              <li
                v-for="(location, index) in locations"
                :key="index"
                class="mb-1"
              >
                <a
                  @click="setLocationFilter(location)"
                  class="cursor-pointer"
                  >{{ location.name }}</a
                >
              </li>
            </ul>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {
      nav: "",
    };
  },
  methods: {
    setArtistFilter(artist) {
      this.$store.commit("setArtistsFilter", artist);
    },
    setLocationFilter(location) {
      return location;
    },
    toggleNav(navTo) {
      if (this.nav == navTo) {
        this.nav = "";
      } else {
        this.nav = navTo;
      }
    },
    viewAll() {},
  },
  computed: {
    artists() {
      return this.$store.state.artists;
    },
    artistsFilter() {
      return this.$store.state.artistsFilter;
    },
    locations() {
      return this.$store.state.locations;
    },
  },
};
</script>
