<template>
    <nav class="w-[300px]">
        <div class="h-[80px]  flex items-center">
            <Link :href="route('dashboard')" class="flex gap-3 items-center px-3">
                <ApplicationLogo
                    class="block h-9 w-auto fill-current text-gray-800"
                />
                File Managment
            </Link>
           
        </div>
        <div class="px-3">
            <CreateNewDropdown></CreateNewDropdown>

            <div class="py-3">
                <NavLink :href="route('myFiles')" :active="$page.url == '/my-files'">
                    My Files
                </NavLink>
                <NavLink href="/1" >
                    Shared with Me
                </NavLink>
                <NavLink href="/1">
                    Starred Files
                </NavLink>
            </div>

        </div>
    </nav>
</template>

<script setup>
import {Link} from '@inertiajs/vue3';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import CreateNewDropdown from "@/Components/app/CreateNewDropdown.vue";
import NavLink from "@/Components/NavLink.vue";
</script>

<style scoped>

</style>