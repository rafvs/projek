<template>
    <div class="login-page">
        <form class="login-card" @submit.prevent="onSubmit" novalidate>
            <h2>Masuk</h2>

            <div class="field">
                <label for="identifier">Email </label>
                <input
                    id="identifier"
                    v-model="form.identifier"
                    :class="{ invalid: errors.identifier }"
                    type="text"
                    autocomplete="username"
                    placeholder="email@contoh.com"
                />
                <div class="error" v-if="errors.identifier">{{ errors.identifier }}</div>
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <div class="password-wrap">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        :class="{ invalid: errors.password }"
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                    />
                    <button type="button" class="toggle" @click="showPassword = !showPassword" :aria-pressed="showPassword">
                        {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}
                    </button>
                </div>
                <div class="error" v-if="errors.password">{{ errors.password }}</div>
            </div>

            <div class="field">
                <label for="secret">Kode Rahasia (opsional)</label>
                <input
                    id="secret"
                    v-model="form.secretCode"
                    type="text"
                    placeholder="Masukkan kode rahasia jika ada"
                />
                <div class="hint">Jika Anda memiliki kode rahasia, gunakan untuk masuk tanpa password.</div>
            </div>

            <div class="extras">
                <label class="remember">
                    <input type="checkbox" v-model="form.remember" /> Ingat saya
                </label>
                <a class="forgot" href="#" @click.prevent="onForgot">Lupa kata sandi?</a>
            </div>

            <div class="actions">
                <button type="submit" :disabled="loading || !isValid">
                    <span v-if="loading">Memproses...</span>
                    <span v-else>Masuk</span>
                </button>
            </div>

            <div class="server-error" v-if="serverError">{{ serverError }}</div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = reactive({
    identifier: '',
    password: '',
    secretCode: '',
    remember: false,
})

const showPassword = ref(false)
const loading = ref(false)
const serverError = ref('')

const errors = reactive({
    identifier: '',
    password: '',
    secretCode: '',
})

const validate = () => {
    errors.identifier = ''
    errors.password = ''
    errors.secretCode = ''
    serverError.value = ''

    if (!form.identifier.trim()) {
        errors.identifier = 'Wajib diisi.'
    } else {
        // optional: basic email format hint (if user used email)
        const isEmail = /\S+@\S+\.\S+/.test(form.identifier)
        if (!isEmail && form.identifier.length < 3) {
            errors.identifier = 'Masukkan email atau username yang valid.'
        }
    }

    // Password/identifier are optional when using secret code, but keep basic checks
    if (form.password && form.password.length < 6) {
        errors.password = 'Minimal 6 karakter.'
    }

    // Secret code is mandatory
    if (!form.secretCode || !form.secretCode.trim()) {
        errors.secretCode = 'Kode rahasia wajib diisi.'
    }

    return !errors.identifier && !errors.password
}

const isValid = computed(() => validate())

const onSubmit = async () => {
    if (!validate()) return
    loading.value = true
    serverError.value = ''
    try {
        // Ganti URL dengan endpoint autentikasi Anda
        // First fetch CSRF token (server stores it in session cookie)
        const baseUrl = 'http://localhost/inventarisBarang/invent_backend/auth.php'
        const csrfRes = await fetch(`${baseUrl}?action=csrf`, { credentials: 'include' })
        const csrfData = await csrfRes.json().catch(() => ({}))
        const csrf = csrfData.csrf || ''

        // Prepare form data (application/x-www-form-urlencoded)
        const params = new URLSearchParams()
        params.append('csrf', csrf)
        // Secret code is mandatory per backend policy
        params.append('code', form.secretCode.trim())

        const res = await fetch(`${baseUrl}?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
            credentials: 'include', // important so PHP session cookie is sent/received
        })

        if (!res.ok) {
            const err = await res.json().catch(() => ({}))
            serverError.value = err.message || 'Gagal login. Periksa kredensial Anda.'
            loading.value = false
            return
        }

        const data = await res.json().catch(() => ({}))
        // auth.php returns { status: 'ok' } on success. We rely on server-side session cookie.
        if (data.status !== 'ok') {
            serverError.value = data.message || 'Gagal login. Periksa kredensial Anda.'
            loading.value = false
            return
        }

        // Redirect ke dashboard/beranda
        // Redirect to tabs (home)
        await router.push('/tabs/tab1').catch(() => {
            window.location.href = '/'
        })
    } catch (e) {
        serverError.value = 'Terjadi kesalahan jaringan.'
        console.error(e)
    } finally {
        loading.value = false
    }
}

const onForgot = () => {
    // Tweak sesuai rute aplikasi Anda
    router.push({ name: 'ForgotPassword' }).catch(() => {})
}
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: #f5f7fb;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}

.login-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 6px 20px rgba(18, 38, 63, 0.08);
}

.login-card h2 {
    margin: 0 0 16px;
    font-weight: 600;
    color: #12263f;
}

.field {
    margin-bottom: 12px;
}

.field label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #344055;
}

.field input[type="text"],
.field input[type="password"],
.field input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    outline: none;
    box-sizing: border-box;
    color: #12263f;
}

.field input.invalid {
    border-color: #f66;
}

.password-wrap {
    display: flex;
    gap: 8px;
    align-items: center;
}

.password-wrap .toggle {
    padding: 8px 10px;
    background: transparent;
    border: 1px solid #e6eefc;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
}

.extras {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 8px 0 16px;
    font-size: 14px;
}

.remember {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #3b485b;
}

.forgot {
    color: #0b66ff;
    text-decoration: none;
    font-size: 13px;
}

.actions {
    margin-top: 6px;
}

.actions button {
    width: 100%;
    padding: 10px 12px;
    background: #0b66ff;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.actions button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.error {
    margin-top: 6px;
    font-size: 13px;
    color: #d8383f;
}

.server-error {
    margin-top: 12px;
    color: #d8383f;
    font-size: 14px;
    text-align: center;
}
</style>