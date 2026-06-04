<script setup>
    import { ref } from 'vue';
    const username = ref('');
    const password = ref('');
    const erreur = ref('');

    const emit = defineEmits(['connexion-reussie'])

    function seConnecter() {
        // Vérification des identifiants (exemple simple)
        if (username.value === 'admin' && password.value === '1234'){
            emit('connexion-reussie')
        } else {
            erreur.value = 'Identifiants incorrects'
        }
    }
</script>

<template>
    <h1>Page Login</h1>
    <form @submit.prevent="seConnecter">
        <input v-model="username" placeholder="Nom d'utilisateur">
        <input v-model="password" type="password" placeholder="mot de passe">
        <button type="submit">Se connecter</button>
        <p v-if="erreur">{{ erreur }}</p>
    </form>    
</template>