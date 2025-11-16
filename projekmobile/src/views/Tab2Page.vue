<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Tambah Barang</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <form @submit.prevent="submitForm">
        <ion-list>
          <ion-item>
            <ion-label position="stacked">Nama Barang</ion-label>
            <ion-input v-model="barang" placeholder="Masukkan nama barang" required></ion-input>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Jumlah</ion-label>
            <ion-input
              v-model.number="jumlah"
              type="number"
              placeholder="Masukkan jumlah"
              required
              min="0"
            ></ion-input>
          </ion-item>
        </ion-list>

        <div style="margin-top:16px; display:flex; gap:8px;">
          <ion-button type="submit" :disabled="loading">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </ion-button>
          <ion-button color="medium" fill="clear" @click="resetForm" :disabled="loading">
            Reset
          </ion-button>
        </div>
      </form>

      <div v-if="message" style="margin-top:16px;">
        <p>{{ message }}</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
  alertController,
} from '@ionic/vue';
import { useRouter } from 'vue-router';

// form model
const barang = ref<string>('');
const jumlah = ref<number | null>(null);

const loading = ref(false);
const message = ref<string>('');

// Router dan alert
const router = useRouter();

const showAlert = async (header: string, msg: string) => {
  const alert = await alertController.create({
    header,
    message: msg,
    buttons: ['OK'],
  });
  await alert.present();
  await alert.onDidDismiss();
};

// sesuaikan endpoint PHP Anda di bawah (contoh: /api/add_item.php)
const API_ENDPOINT = 'http://localhost/projekmobile/invent_backend/addData.php';

async function submitForm() {
  if (!barang.value || jumlah.value === null) {
    message.value = 'Nama dan jumlah harus diisi.';
    return;
  }

  loading.value = true;
  message.value = '';

  try {
    const res = await fetch(API_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        nama: barang.value,
        jumlah: jumlah.value,
      }),
    });

    const data = await res.json().catch(() => ({}));

    if (res.ok && data.status === 'success') {
      const msg = data.message || 'Data berhasil ditambahkan.';
      await showAlert('Sukses', msg);
      resetForm();
      router.replace('/tabs/tab1');
    } else {
      const errMsg = (data.errors && data.errors.join(', ')) || data.message || 'Terjadi kesalahan pada server.';
      await showAlert('Gagal', errMsg);
      message.value = errMsg;
    }
  } catch (err: any) {
    const errMsg = 'Gagal menghubungi server: ' + (err?.message || err);
    await showAlert('Error', errMsg);
    message.value = errMsg;
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  barang.value = '';
  jumlah.value = null;
  message.value = '';
}
</script>
