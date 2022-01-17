<template>
  <div class="col-span-9 text-white text-center py-10">
    <p class="text-5xl lg:text-7xl font-medium mb-2">
      {{ index }}
    </p>
    <p class="hidden text-2xl font-medium">The Beginning</p>
  </div>
</template>
<script>
export default {
  props: {
    index: {
      type: String,
    },
  },
};
</script>
