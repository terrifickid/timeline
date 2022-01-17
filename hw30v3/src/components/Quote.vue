<template>
  <div class="text-left text-white py-10">
    <p class="text-3xl lg:text-4xl leading-tight mb-10">{{ quote }}</p>
    <p class="text-2xl lg:text-3xl" v-if="citation">- {{ citation }}</p>
  </div>
</template>
<script>
export default {
  props: {
    quote: {
      required: true,
      type: String,
    },
    citation: {
      required: false,
      type: String,
    },
  },
};
</script>
