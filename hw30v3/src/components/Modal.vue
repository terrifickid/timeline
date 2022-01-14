<template>
  <div
    id="contentModal"
    class="fixed z-20 w-screen"
    v-if="activeEntry.date"
    v-bind:class="{ modalToggle: isModalActive }"
  >
    <div class="grid grid-cols-12">
      <div class="hidden lg:block lg:col-span-2"></div>
      <div
        class="col-span-12 lg:col-span-10 h-screen bg-white text-black overflow-y-scroll"
      >
        <div class="grid grid-cols-12">
          <div class="col-span-12 px-4 md:px-20">
            <div
              class="flex items-center border-b border-black mb-10 pt-6 pb-8 md:pt-12"
            >
              <div class="flex items-center w-full">
                <p class="text-xl md:text-3xl font-medium">
                  {{ activeEntry.date[2] }}
                </p>
                <div class="hidden sm:flex mx-auto md:ml-24">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 cursor-pointer text-gray-300"
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
                  <span
                    class="hidden md:inline text-gray-300 cursor-pointer ml-2"
                    >Previous</span
                  >
                  <span class="mx-6">1 / 4</span>
                  <span class="hidden md:inline cursor-pointer mr-2">Next</span>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 cursor-pointer"
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
                <a @click="close()" class="flex ml-auto cursor-pointer">
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
                <img :src="activeEntry.media_gallery[0].image_file.url" />
                <p class="text-sm mt-2">
                  {{ activeEntry.media_gallery[0].image_title }}
                </p>
                <p class="text-sm mt-2">
                  {{ activeEntry.media_gallery[0].image_footnote }}
                </p>
              </div>
              <div class="col-span-5 md:col-span-3">
                <h1
                  class="text-3xl md:text-5xl font-medium mb-6"
                  v-html="activeEntry.title"
                ></h1>
                <p
                  class="font-bold mb-6"
                  v-html="activeEntry.short_description"
                ></p>

                <p v-html="activeEntry.long_description"></p>
              </div>
            </div>
          </div>

          <div class="col-span-1"></div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  computed: {
    isModalActive() {
      return this.$store.state.isModalActive;
    },
    activeEntry() {
      return this.$store.state.activeEntry;
    },
  },
  methods: {
    close() {
      this.$store.commit("setModal", false);
      document.body.style.overflow = "scroll";
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
