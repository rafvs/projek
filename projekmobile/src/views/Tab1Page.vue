<template>
  <ion-page>
    <ion-header>
      <navbar />
    </ion-header>
    <ion-content :fullscreen="true">
      <div style="display: block; text-align: end; margin: 16px;">
        <ion-button color="primary" @click="router.push('/tabs/tab2')">Tambah Item</ion-button>
      </div>
      <ion-card v-for="item in items" :key="item.id">
        <ion-card-header>
          <ion-card-title>{{ item.barang }}</ion-card-title>
          <ion-card-subtitle>Stok: {{ item.jumlah }}</ion-card-subtitle>
        </ion-card-header>

        <ion-button fill="clear" color="danger" @click="onDelete(item.id)">Delete</ion-button>
        <ion-button fill="clear" color="success" @click="router.push(`/tabs/edit/${item.id}`)">Edit</ion-button>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent } from '@ionic/vue';
import navbar from '@/components/navbar.vue';
import axios from 'axios';
import router from '@/router';
import { ref, onMounted } from 'vue';

const items = ref<any[]>([]);

// Ganti dengan endpoint API PHP Anda
const API_URL = 'http://localhost/projekmobile/invent_backend/showData.php';
const DELETE_URL = 'http://localhost/projekmobile/invent_backend/deleteData.php';

const fetchItems = async () => {
  const { data } = await axios.get(API_URL);
  // Sesuaikan dengan struktur response backend Anda
  items.value = Array.isArray(data) ? data : (data.data ?? []);
  
};

const onDelete = async (id: number) => {
  if (!id) return;
  if (!confirm('Hapus item ini?')) return;
  try {
    await axios.post(DELETE_URL, new URLSearchParams({ id: String(id) }));
    await fetchItems();
  } catch (e) {
    console.error('Gagal menghapus:', e);
  }
};


onMounted(fetchItems);
</script>
