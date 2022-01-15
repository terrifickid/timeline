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
        "https://hw30secure.wpengine.com/wp-json/wp/v2/timeline/"
      );
      console.log("loaded!");
      var years = [];
      var timeline = res.data.map((entry) => {
        var d = entry.acf;
        d.date = entry.acf.date.split(",");
        if (!years.includes(d.date[2])) {
          years.push(d.date[2]);
          d.showDate = true;
        }
        d.title = entry.title.rendered;
        return d;
      });
      commit("setTimeline", timeline);
      commit("setActiveEntry", timeline[0]);
    },
  },
  modules: {},
});
