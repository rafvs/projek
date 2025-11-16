<template>
    <nav>
        <div class="brand">inventaris Barang</div>
        <ion-button color="light">
            logout
        </ion-button>
    </nav>
</template>


<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent } from '@ionic/vue';
</script>

<style scoped>
nav {
    justify-content: space-between;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    height: 56px;
    background-color: #3880ff;
    color: white;
}

.brand {
    font-weight: bold;
    font-size: 1.5em;
}
</style>