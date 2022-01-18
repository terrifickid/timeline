<template>
  <!-- timeline -->
  <div style="background: linear-gradient(177.84deg, #110223 0%, #73173e 100%)">
    <div
      class="container px-4 mx-auto"
      style="background: url('/hw30img/white.png') center center repeat-y"
    >
      <div class="grid grid-cols-12">
        <div class="hidden md:block md:col-span-2 pt-10">
          <YearScroller></YearScroller>
        </div>
        <div class="col-span-12 md:col-span-8">
          <template v-for="(data, index) in timestream" :key="index">
            <YearBlock :index="index" />
            <template
              v-for="(entry, index) in data.entries.slice(
                0,
                showCountFunc(index)
              )"
              :key="index"
            >
              <EntryLeft :entry="entry" />
              <Quote
                :quote="entry.quote"
                :citation="entry.citation"
                v-if="entry.quote"
              />
            </template>
            <Discover
              v-if="data.entries.length > showCountFunc(index)"
              @click="showCount[index] = data.entries.length"
              >{{ data.entries.length - showCountFunc(index) }}</Discover
            >
          </template>
        </div>
      </div>
    </div>
  </div>
  <!-- timeline -->
</template>
<script>
import YearScroller from "../components/YearScroller.vue";
import YearBlock from "../components/YearBlock.vue";
import EntryLeft from "../components/EntryLeft.vue";
import Quote from "../components/Quote.vue";
import Discover from "../components/Discover.vue";
export default {
  components: {
    YearScroller,
    YearBlock,
    EntryLeft,
    Quote,
    Discover,
  },
  data() {
    return {
      showCount: {},
    };
  },
  methods: {
    showCountFunc(t) {
      if (this.showCount[t]) return this.showCount[t];
      return 2;
    },
  },
  computed: {
    timestream() {
      return this.$store.getters.timestream;
    },
  },
};
</script>
