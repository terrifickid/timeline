<template>
  <div class="grid grid-cols-9 text-white mb-10 lg:mb-20">
    <div class="col-span-4 text-right">
      <h3 class="text-sm mb-1">Category Name</h3>
      <p class="font-medium text-xl lg:text-3xl" v-html="entry.title"></p>
    </div>
    <div class="col-span-1 flex justify-center">
      <div class="w-6 h-6 lg:w-8 lg:h-8 rounded-full bg-white"></div>
    </div>
    <div class="col-span-4">
      <a @click="open(entry)" class="cursor-pointer"
        ><img
          v-if="entry.media_gallery[0]"
          :src="entry.media_gallery[0].image_file.url"
      /></a>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    entry: {
      required: true,
      type: Object,
    },
  },
  methods: {
    open(entry) {
      this.$store.commit("setActiveEntry", entry);
      this.$store.commit("setModal", true);
      document.body.style.overflow = "hidden";
    },
  },
};
</script>
