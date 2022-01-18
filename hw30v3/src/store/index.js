import { createStore } from "vuex";

import axios from "axios";
export default createStore({
  state: {
    timeline: [],
    isModalActive: false,
    activeEntry: {},
    artists: [],
    artistsFilter: [],
    locations: [],
    locationsFilter: [],
  },
  getters: {
    timestream: (state) => {
      var time = state.timeline.filter((entry) => {
        //Artist Filter
        if (state.artistsFilter.length) {
          if (!state.artistsFilter.includes(entry.artists[0].slug))
            return false;
        }
        return true;
      });
      var t = {};
      time.forEach((element) => {
        //Categorize Timestream by Year
        if (!t[element.date[2]])
          t[element.date[2]] = { showCount: 2, entries: [] };
        t[element.date[2]].entries.push(element);
      });
      console.log(t);
      return t;
    },
    isModalActive: (state) => {
      return state.isModalActive;
    },
    activeEntry: (state) => {
      return state.activeEntry;
    },
    artists: (state) => {
      return state.artists;
    },
    artistsFilter: (state) => {
      return state.artistsFilter;
    },
    locations: (state) => {
      return state.locations;
    },
    locationsFilter: (state) => {
      return state.locationsFilter;
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
    setArtists(state, artists) {
      state.artists = artists;
    },
    setArtistsFilter(state, artist) {
      if (state.artistsFilter.includes(artist.slug)) {
        var index = state.artistsFilter.indexOf(artist.slug);
        if (index !== -1) {
          state.artistsFilter.splice(index, 1);
        }
      } else {
        state.artistsFilter.push(artist.slug);
      }
    },
    setLocations(state, locations) {
      state.locations = locations;
    },
    setLocationsFilter(state, locationsFilter) {
      state.locationsFilter = locationsFilter;
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

      //Map Entries Format
      var timeline = res.data.map((entry) => {
        var d = entry.acf;
        d.date = entry.acf.date.split(",");
        d.title = entry.title.rendered;
        d.artists = entry.artists;
        d.locations = entry.locations;
        return d;
      });

      var artists = [];
      var locations = [];

      //Process Timeline Entries
      timeline.forEach((element) => {
        //Populate Artists
        element.artists.forEach((artist) => {
          if (!artists.some((e) => e.term_id === artist.term_id))
            artists.push(artist);
        });
        //Populate Locations
        element.locations.forEach((location) => {
          if (!locations.some((e) => e.term_id === location.term_id))
            locations.push(location);
        });
      });

      commit("setArtists", artists);
      commit("setLocations", locations);
      commit("setTimeline", timeline);
      commit("setActiveEntry", timeline[0]);
    },
  },
  modules: {},
});
