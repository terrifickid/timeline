<template>
  <div>
    <Header></Header>
    <TimelineText></TimelineText>
    <StickyNav></StickyNav>
    <TimelinePane></TimelinePane>
  </div>
</template>
<script>
import Header from "../components/Header.vue";
import TimelineText from "../components/TimelineText.vue";
import StickyNav from "../components/StickyNav.vue";
import TimelinePane from "../components/TimelinePane.vue";
export default {
  components: {
    Header,
    TimelineText,
    StickyNav,
    TimelinePane,
  },
};
</script>
