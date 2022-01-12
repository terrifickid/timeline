<template>
  <router-view />
</template>
<script>
export default {
  mounted() {
    this.$store.dispatch("loadData");
  },
};
</script>
<style lang="scss"></style>
