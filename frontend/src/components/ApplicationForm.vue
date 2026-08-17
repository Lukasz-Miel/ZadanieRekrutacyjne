<script setup>
import { reactive, ref } from 'vue'

const currentStep = ref(1)
const submittedData = ref(null)
const serverErrors = ref([])
const isSubmitting = ref(false)

const form = reactive({
    firstName: '',
    lastName: '',
    birthDate: '',

    phone: '',
    email: '',

    workExperiences: [
        {
            company: '',
            position: '',
            dateFrom: '',
            dateTo: ''
        }
    ]
})

const errors = reactive({
    firstName: '',
    lastName: '',
    birthDate: '',

    phone: '',
    email: '',

    workExperiences: []
})

const today = new Date().toISOString().split('T')[0]
const minDate = '1900-01-01'

function validateBasicData() {
    errors.firstName = ''
    errors.lastName = ''
    errors.birthDate = ''

    let valid = true

    if (!form.firstName.trim()) {
        errors.firstName = 'Imię jest wymagane.'
        valid = false
    }

    if (!form.lastName.trim()) {
        errors.lastName = 'Nazwisko jest wymagane.'
        valid = false
    }

    if (!form.birthDate) {
        errors.birthDate = 'Data urodzenia jest wymagana.'
        valid = false
    } else if (form.birthDate >= today) {
        errors.birthDate =
            'Data urodzenia musi być wcześniejsza niż dzisiaj.'
        valid = false
    }

    return valid
}

function validateContactData() {
    errors.phone = ''
    errors.email = ''

    let valid = true

    const phoneRegex = /^\+?[0-9\s-]{9,20}$/

    const emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    if (!form.phone.trim()) {
        errors.phone = 'Numer telefonu jest wymagany.'
        valid = false
    } else if (!phoneRegex.test(form.phone)) {
        errors.phone = 'Podaj poprawny numer telefonu.'
        valid = false
    }

    if (!form.email.trim()) {
        errors.email = 'E-mail jest wymagany.'
        valid = false
    } else if (!emailRegex.test(form.email)) {
        errors.email = 'Podaj poprawny adres e-mail.'
        valid = false
    }

    return valid
}

function validateWorkExperience() {
    errors.workExperiences = []

    let valid = true

    form.workExperiences.forEach((experience, index) => {

        const itemErrors = {}

        if (!experience.company.trim()) {
            itemErrors.company =
                'Firma jest wymagana.'
        }

        if (!experience.position.trim()) {
            itemErrors.position =
                'Stanowisko jest wymagane.'
        }

        if (!experience.dateFrom) {
            itemErrors.dateFrom =
                'Data od jest wymagana.'
        }

        if (!experience.dateTo) {
            itemErrors.dateTo =
                'Data do jest wymagana.'
        }

        if (
            experience.dateFrom &&
            experience.dateTo &&
            experience.dateFrom > experience.dateTo
        ) {
            itemErrors.dateFrom =
                'Data od nie może być późniejsza niż data do.'

            itemErrors.dateTo =
                'Data do nie może być wcześniejsza niż data od.'
        }

        errors.workExperiences[index] = itemErrors

        if (Object.keys(itemErrors).length > 0) {
            valid = false
        }
    })

    return valid
}

function validateStep(step) {
    if (step === 1) {
        return validateBasicData()
    }

    if (step === 2) {
        return validateContactData()
    }

    if (step === 3) {
        return validateWorkExperience()
    }

    return true
}

function goToStep(step) {
    currentStep.value = step
}

function nextStep() {
    if (validateStep(currentStep.value)) {
        currentStep.value++
    }
}

function previousStep() {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

function addExperience() {
    form.workExperiences.push({
        company: '',
        position: '',
        dateFrom: '',
        dateTo: ''
    })
}

function removeExperience(index) {
    form.workExperiences.splice(index, 1)
    errors.workExperiences.splice(index, 1)
}

async function submitForm() {

    serverErrors.value = []

    const basicValid = validateBasicData()
    const contactValid = validateContactData()
    const workValid = validateWorkExperience()

    if (!basicValid || !contactValid || !workValid) {
        return
    }

    isSubmitting.value = true

    try {
        const response = await fetch(
            'http://127.0.0.1:8000/api/application',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify(form)
            }
        )

        const result = await response.json()

        if (!response.ok) {
            serverErrors.value =
                result.errors ?? ['Wystąpił błąd.']

            return
        }

        submittedData.value = result.data

    } catch (error) {

        serverErrors.value = [
            'Nie udało się połączyć z serwerem.'
        ]

    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>

    <div class="container">

        <h1>Formularz aplikacyjny</h1>

        <div class="box">

            <div
                v-if="serverErrors.length"
                class="server-errors"
            >
                <p
                    v-for="error in serverErrors"
                    :key="error"
                >
                    {{ error }}
                </p>
            </div>

            <div v-if="currentStep === 1">

                <h2>Dane podstawowe</h2>

                <div class="field">

                    <label>Imię</label>

                    <input
                        v-model="form.firstName"
                        type="text"
                    >

                    <span class="error">
                        {{ errors.firstName }}
                    </span>

                </div>

                <div class="field">

                    <label>Nazwisko</label>

                    <input
                        v-model="form.lastName"
                        type="text"
                    >

                    <span class="error">
                        {{ errors.lastName }}
                    </span>

                </div>

                <div class="field">

                    <label>Data urodzenia</label>

                    <input
                        v-model="form.birthDate"
                        type="date"
                        :min="minDate"
                        :max="today"
                    >

                    <span class="error">
                        {{ errors.birthDate }}
                    </span>

                </div>

            </div>

            <div v-if="currentStep === 2">

                <h2>Dane kontaktowe</h2>

                <div class="field">

                    <label>Telefon</label>

                    <input
                        v-model="form.phone"
                        type="text"
                        placeholder="+48 123 456 789"
                    >

                    <span class="error">
                        {{ errors.phone }}
                    </span>

                </div>

                <div class="field">

                    <label>E-mail</label>

                    <input
                        v-model="form.email"
                        type="email"
                    >

                    <span class="error">
                        {{ errors.email }}
                    </span>

                </div>

            </div>

            <div v-if="currentStep === 3">

                <h2>Doświadczenie zawodowe</h2>

                <table>

                    <thead>

                        <tr>
                            <th>Firma</th>
                            <th>Stanowisko</th>
                            <th>Data od</th>
                            <th>Data do</th>
                            <th>Akcje</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr
                            v-for="(experience, index) in form.workExperiences"
                            :key="index"
                        >

                            <td>

                                <input
                                    v-model="experience.company"
                                    type="text"
                                >

                                <span class="error">
                                    {{ errors.workExperiences[index]?.company }}
                                </span>

                            </td>

                            <td>

                                <input
                                    v-model="experience.position"
                                    type="text"
                                >

                                <span class="error">
                                    {{ errors.workExperiences[index]?.position }}
                                </span>

                            </td>

                            <td>

                                <input
                                    v-model="experience.dateFrom"
                                    type="date"
                                    :min="minDate"
                                >

                                <span class="error">
                                    {{ errors.workExperiences[index]?.dateFrom }}
                                </span>

                            </td>

                            <td>

                                <input
                                    v-model="experience.dateTo"
                                    type="date"
                                    :max="today"
                                >

                                <span class="error">
                                    {{ errors.workExperiences[index]?.dateTo }}
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    @click="removeExperience(index)"
                                    :disabled="form.workExperiences.length === 1"
                                >
                                    Usuń
                                </button>

                            </td>

                        </tr>

                    </tbody>

                </table>

                <button
                    type="button"
                    @click="addExperience"
                >
                    + Dodaj doświadczenie
                </button>

            </div>

            <div class="navigation">

                <button
                    v-if="currentStep > 1"
                    type="button"
                    @click="previousStep"
                    class="prev"
                >
                    Wstecz
                </button>

                <button
                    v-if="currentStep < 3"
                    type="button"
                    @click="nextStep"
                    class="next"
                >
                    Dalej
                </button>

                <button
                    v-if="currentStep === 3"
                    type="button"
                    @click="submitForm"
                    :disabled="isSubmitting"
                    class="next"
                >
                    {{ isSubmitting ? 'Zapisywanie...' : 'Wyślij' }}
                </button>

            </div>

            <div
                v-if="submittedData"
                class="result"
            >

                <h2>Dane zostały zapisane</h2>

                <p>
                    <strong>Imię:</strong>
                    {{ submittedData.firstName }}
                </p>

                <p>
                    <strong>Nazwisko:</strong>
                    {{ submittedData.lastName }}
                </p>

                <p>
                    <strong>Data urodzenia:</strong>
                    {{ submittedData.birthDate }}
                </p>

                <p>
                    <strong>Telefon:</strong>
                    {{ submittedData.phone }}
                </p>

                <p>
                    <strong>E-mail:</strong>
                    {{ submittedData.email }}
                </p>

                <h3>Doświadczenie zawodowe</h3>

                <table>

                    <thead>

                        <tr>
                            <th>Firma</th>
                            <th>Stanowisko</th>
                            <th>Od</th>
                            <th>Do</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr
                            v-for="experience in submittedData.workExperiences"
                            :key="experience.company + experience.dateFrom"
                        >

                            <td>{{ experience.company }}</td>

                            <td>{{ experience.position }}</td>

                            <td>{{ experience.dateFrom }}</td>

                            <td>{{ experience.dateTo }}</td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</template>

<style scoped>



.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px;
    font-family: Arial, sans-serif;
    color: var(--text);
}

.box {
    max-height: 75vh;
    overflow-y: auto;
    padding: 25px;
    border-radius: 15px;
    background: var(--surface);
}

h2 {
    margin-top: 0;
}

.field {
    margin-bottom: 20px;
}

.field label {
    display: block;
    margin-bottom: 5px;
}

.field input {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
}


input{
    background-color: var(--surface);
    color: var(--text);

    border: 1px solid var(--border);
    border-radius: 6px;

    outline: none;

    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.input:focus,
td input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2);
}

input::placeholder {
    color: var(--text-muted);
}

input[type="date"] {
    color-scheme: dark;
}

.error {
    display: block;
    color: var(--error);
    font-size: 13px;
    margin-top: 5px;
}

.server-errors {
    padding: 10px;
    margin-bottom: 20px;
    color: var(--error);
    border: 1px solid red;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

th {
    border-bottom: 1px solid var(--border);
}

th,
td {
    padding: 8px;
    text-align: center;
}

td input {
    width: 100%;
    box-sizing: border-box;
}

.navigation {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.navigation button,
button {
    padding: 8px 15px;
    cursor: pointer;
    background: var(--primary);
    color: var(--background);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.2s;
}

.navigation button:hover,
button:hover {
    background-color: var(--primary-dark);
    color: #ffffff;
}

.prev {
    margin-right: auto;
}

.next {
    margin-left: auto;
}

.result {
    margin-top: 40px;
    padding: 20px;
    border: 1px solid var(--border);
}

</style>