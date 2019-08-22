import Vue from "vue";
import Router from "vue-router";

Vue.use(Router);

const router = new Router({
    base: window.bi.base,
    mode: "history",
    routes: [
        {
            name: "homepage",
            path: "/",
            redirect: "/ceg"
        },
        {
            name: "dashboard",
            path: "/:dashboardKey",
            component: Dashboard,
            props: route => {
                return {
                    dashboardKey: route.params.dashboardKey
                };
            }
        }
    ]
});

import Nav from "./components/Nav.vue";
import Dashboard from "./components/Dashboard.vue";
import Toast from "./components/Toast.vue";

Vue.component("bi-nav", Nav);
Vue.component("bi-dashboard", Dashboard);
Vue.component("bi-toast", Toast);

new Vue({
    el: "#laravel-bi",
    router,
    mounted() {
        console.log("Laravel Bi started!");
    }
});
