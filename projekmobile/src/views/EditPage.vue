<template>
    <ion-page>
        <ion-header>
            <ion-toolbar>
                <ion-buttons slot="start">
                    <ion-back-button default-href="/" />
                </ion-buttons>
                <ion-title>Edit Item</ion-title>
            </ion-toolbar>
        </ion-header>

        <ion-content :fullscreen="true">
            <ion-list>
                <ion-item>
                    <ion-label position="stacked">Name</ion-label>
                    <ion-input v-model="item.barang" placeholder="Nama item" />
                </ion-item>

                <ion-item>
                    <ion-label position="stacked">Quantity</ion-label>
                    <ion-input v-model.number="item.jumlah" type="number" placeholder="Jumlah" />
                </ion-item>
            </ion-list>

            <div style="padding:16px;">
                <ion-button expand="block" @click="save" :disabled="loading">Simpan</ion-button>
                <ion-button expand="block" fill="clear" @click="cancel" :disabled="loading">Batal</ion-button>
            </div>

            <ion-toast v-model:isOpen="toastShow" :message="toastMessage" duration="2000" />

            <ion-loading :is-open="loading" message="Memproses..." />
        </ion-content>
    </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
    IonList, IonItem, IonLabel, IonInput, IonButton,
    IonButtons, IonBackButton, IonToast, IonLoading
} from '@ionic/vue';

interface Item {
    id?: string;
    barang: string;
    jumlah?: number;
}

const route = useRoute();
const router = useRouter();

// Ambil id dari params atau query
const id = String(route.params.id ?? route.query.id ?? '');

const item = ref<Item>({ id, barang: '', jumlah: 0 });
const loading = ref(false);
const toastShow = ref(false);
const toastMessage = ref('');
const API_GET = `http://localhost/projekmobile/invent_backend/editData.php?id=${id}`;
const API_UPDATE = 'http://localhost/projekmobile/invent_backend/editData.php';

onMounted(async () => {
  if (!id) return;
  loading.value = true;
  try {
    const res = await fetch(API_GET);
    const raw = await res.json().catch(() => ({}));
    const d = raw.data ?? raw;
    const row = Array.isArray(d) ? (d[0] ?? {}) : d;

    item.value.barang = row.barang ?? row.nama ?? '';
    const j = Number(d.jumlah ?? 0);
    item.value.jumlah = Number.isNaN(j) ? 0 : j;
  } catch (e) {
    toastMessage.value = 'Gagal memuat data.';
    toastShow.value = true;
  } finally {
    loading.value = false;
  }
});

async function save() {
  loading.value = true;
  try {
    const payload = {
      id,                                   // WAJIB: kirim id di body
      barang: item.value.barang ?? '',
      jumlah: item.value.jumlah ?? 0,
    };

    const res = await fetch(API_UPDATE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.status !== 'success') throw new Error((data.errors && data.errors.join(', ')) || data?.message || 'Gagal');

    toastMessage.value = data?.message || 'Data berhasil diperbarui';
    toastShow.value = true;
    setTimeout(() => router.back(), 800);
  } catch (e: any) {
    toastMessage.value = e?.message ?? 'Gagal menyimpan data';
    toastShow.value = true;
  } finally {
    loading.value = false;
  }
}

function cancel() {
    router.back();
}
</script>
