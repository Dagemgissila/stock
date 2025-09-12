<template>
    <a-layout v-if="frontWarehouse.online_store_enabled == 1">
        <a-layout-header :style="{ background: '#2874f0', padding: 0 }">
            <a-row type="flex" justify="center">
                <a-col :span="20">
                    <a-row type="flex" justify="space-between">
                        <a-col
                            :xs="12"
                            :sm="12"
                            :md="innerWidth >= 768 ? 6 : 12"
                            :lg="4"
                            :xl="4"
                            v-if="frontWarehouse && frontWarehouse.slug"
                        >
                            <LeftSidebar />

                            <router-link
                                :to="{ name: 'front.homepage' }"
                                class="logo-link"
                            >
                                <div class="logo-container">
                                    <img
                                        :style="{
                                            width: innerWidth >= 768 ? '150px' : '110px',
                                            maxHeight:'50px'
                                        }"
                                        :src="frontWarehouse.dark_logo_url"
                                        alt="Warehouse Logo"
                                    />
                                    <span
                                        :style="{
                                            fontSize: innerWidth >= 768 ? '20px' : '16px',
                                            fontWeight: 'bold',
                                            color: '#fff',
                                            marginLeft: '10px'
                                        }"
                                    >
                                        {{ frontWarehouse.name }}
                                    </span>
                                </div>
                            </router-link>
                        </a-col>
                        <a-col v-if="innerWidth >= 768" :md="12" :lg="12" :xl="12">
                        </a-col>
                        <a-col
                            :xs="12"
                            :sm="12"
                            :md="innerWidth >= 768 ? 6 : 12"
                            :lg="8"
                            :xl="8"
                        >
                            <div :style="{ textAlign: 'right' }">
                                <CheckoutDrawer @openLoginModal="openLoginModal" />
                                <Login
                                    :modalVisible="loginModalVisible"
                                    @modalClosed="loginModalClosed"
                                />
                            </div>
                        </a-col>
                    </a-row>
                </a-col>
            </a-row>
        </a-layout-header>
        <a-layout-content>
            <div>
                <div :style="{ background: '#fff' }" class="subheader">
                    <a-row type="flex" justify="center">
                        <a-col :span="20">
                            <a-row>
                                <!-- Additional Subheader Content -->
                            </a-row>
                        </a-col>
                    </a-row>
                </div>

                <router-view></router-view>
                <Footer />
            </div>
        </a-layout-content>
    </a-layout>
    <div v-else class="no-online-store-container">
        <a-result
            status="warning"
            :title="$t('warehouse.no_online_store_exists')"
        ></a-result>
    </div>
</template>

<script>
import { defineComponent, ref, computed } from "vue";
import { useStore } from "vuex";
import { DownOutlined, MenuOutlined, AppstoreOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import ProductCard from "../components/ProductCard.vue";
import Footer from "./Footer.vue";
import CheckoutDrawer from "../components/CheckoutDrawer.vue";
import Login from "../components/Login.vue";
import LeftSidebar from "./LeftSidebar.vue";

export default defineComponent({
    components: {
        DownOutlined,
        MenuOutlined,
        AppstoreOutlined,
        ProductCard,
        Footer,
        CheckoutDrawer,
        Login,
        LeftSidebar,
    },
    setup() {
        const store = useStore();
        const { frontAppSetting } = common();
        const frontWarehouse = computed(() => {
            return store.getters["auth/frontWarehouse"];
        });
        const searchValue = ref("");
        const loginModalVisible = ref(false);

        const openLoginModal = () => {
            loginModalVisible.value = true;
        };

        const loginModalClosed = () => {
            loginModalVisible.value = false;
        };

        return {
            frontAppSetting,
            searchValue,
            openLoginModal,
            loginModalClosed,
            loginModalVisible,
            frontWarehouse,
            innerWidth: window.innerWidth,
        };
    },
});
</script>

<style lang="less" scoped>
.logo-container {
    display: flex;
    align-items: center;
}

.no-online-store-container {
    height: 100%;
    width: 100%;
    display: flex;
    position: fixed;
    align-items: center;
    justify-content: center;
    background: #f8f8ff;
}

</style>
