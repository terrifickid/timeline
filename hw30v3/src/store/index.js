import { createStore } from "vuex";

import axios from "axios";
export default createStore({
  state: {
    timeline: [],
    isModalActive: false,
    activeEntry: {},
  },
  getters: {
    timeline: (state) => {
      return state.timeline;
    },
    isModalActive: (state) => {
      return state.isModalActive;
    },
    activeEntry: (state) => {
      return state.activeEntry;
    },
  },
  mutations: {
    setTimeline(state, timeline) {
      state.timeline = timeline;
    },
    setModal(state, status) {
      state.isModalActive = status;
    },
    setActiveEntry(state, entry) {
      state.activeEntry = entry;
    },
  },
  actions: {
    async loadData({ commit }) {
      console.log("Loading..");
      //Load data
      var res = await axios.get(
        "https://hw30secure1.wpengine.com/wp-json/wp/v2/timeline/"
      );
      console.log("loaded!");

      //Create Timestream Array
      var timestream = {};

      //Map Entries Format
      var timeline = res.data.map((entry) => {
        var d = entry.acf;
        d.date = entry.acf.date.split(",");
        d.title = entry.title.rendered;
        return d;
      });

      //Categorize by Year
      timeline.forEach((element) => {
        if (!timestream[element.date[2]])
          timestream[element.date[2]] = { showCount: 2, entries: [] };
        timestream[element.date[2]].entries.push(element);
      });

      console.log(timestream);

      commit("setTimeline", timestream);
      commit(
        "setActiveEntry",
        timestream[Object.keys(timestream)[0]].entries[0]
      );
    },
  },
  modules: {},
});
