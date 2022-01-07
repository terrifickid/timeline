import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    name: "Timeline",
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: function () {
      return import(/* webpackChunkName: "timeline" */ "../views/Timeline.vue");
    },
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

export default router;
