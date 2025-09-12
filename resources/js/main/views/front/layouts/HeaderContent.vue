<template>
    <a-row type="flex" justify="space-between">
        <a-col :xs="12" :sm="12" :md="innerWidth >= 768 ? 6 : 12" :lg="4" :xl="4" v-if="frontWarehouse.slug">
            <LeftSidebar />
            <router-link :to="{ name: 'front.homepage' }">
                <img :style="{ width: innerWidth >= 768 ? '150px' : '110px' }" :src="frontWarehouse.dark_logo_url" />
            </router-link>
        </a-col>

        <a-col v-if="innerWidth >= 768" :md="12" :lg="12" :xl="12"></a-col>

        <a-col :xs="12" :sm="12" :md="innerWidth >= 768 ? 6 : 12" :lg="8" :xl="8">
            <div :style="{ textAlign: 'right' }">
                <CheckoutDrawer @openLoginModal="openLoginModal" />
                <Login :modalVisible="loginModalVisible" @modalClosed="loginModalClosed" />
            </div>
        </a-col>
    </a-row>
</template>

<script>
import LeftSidebar from "./LeftSidebar.vue";
import CheckoutDrawer from "../components/CheckoutDrawer.vue";

import Login from "../components/Login.vue";

export default {
    props: {
        frontWarehouse: Object,
        innerWidth: Number,
        loginModalVisible: Boolean,
    },
    components: { LeftSidebar, CheckoutDrawer, Login },
    methods: {
        openLoginModal() {
            this.$emit("openLoginModal");
        },
        loginModalClosed() {
            this.$emit("loginModalClosed");
        },
    },
};
</script>
