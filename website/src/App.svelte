<script>
    import {createEditor} from "./editor.js";
    import {onMount} from "svelte";

    let editor, editorMount;
    onMount(() => {
        const resultMatch = window.location.pathname.match(/^\/r\/([0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})$/i);
        if (resultMatch !== null) {
            fetchResult(resultMatch[1])
                .then(() => {
                    editor.dispatch({changes: {
                            from: 0,
                            to: editor.state.doc.length,
                            insert: data.code
                        }})
                })
        }
        editor = createEditor(editorMount, data.code, update => data.code = update)
    })

    const apiUrl = 'https://gkyhj54sul.execute-api.us-east-1.amazonaws.com/prod';
    const data = {
        code: `<?php\n\nmodule_load_include('inc', 'foo', 'node.admin');\n\n\\Drupal::moduleHandler()->loadInclude('node', 'inc', 'node.admin');`,
        level: '9',
        strictRules: false,
        bleedingEdge: false,
        treatPhpDocTypesAsCertain: true
    };
    let processing = false;
    let result = JSON.parse(`{
  "tabs": [
    {
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ],
      "title": "PHP 8.1 – 8.3 (2 errors)"
    }
  ],
  "versionedErrors": [
    {
      "phpVersion": 80100,
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ]
    },
    {
      "phpVersion": 80200,
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ]
    },
    {
      "phpVersion": 80300,
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ]
    }
  ],
  "id": "d22c810f-dd6c-4769-aa4d-41c3be5792f4"
}
`);
    async function fetchResult(id) {
        result = null;
        processing = true;
        try {
            const response = await fetch(`${apiUrl}/result?id=${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            result = await response.json();
            data.code = result.code;
            data.level = result.level;
            data.strictRules = result.strictRules;
            data.bleedingEdge = result.bleedingEdge;
            data.treatPhpDocTypesAsCertain = result.treatPhpDocTypesAsCertain;
        } catch (error) {
            console.error(error);
        } finally {
            processing = false;
        }
    }
    async function analyse (event) {
        event.preventDefault();
        await doAnalyse(false);
    }

    async function doAnalyse(saveResult) {
        result = null;
        processing = true;
        if (typeof window.fathom !== 'undefined') {
            window.fathom.trackEvent('analyse code');
        }
        try {
            const response = await fetch(`${apiUrl}/analyse`, {
                method: 'POST',
                body: JSON.stringify({
                    ...data,
                    saveResult
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            result = await response.json();
        } catch (error) {
            console.error(error);
        } finally {
            processing = false;
        }
    }

    async function share() {
        const id = result?.id;
        if (!id) {
            await doAnalyse(true);
            window.history.replaceState({}, '', '/r/' + result.id);
        }
        if (typeof window.navigator.share !== 'undefined') {
            await window.navigator.share({url: window.location.href});
        } else if (typeof window.navigator.clipboard !== 'undefined') {
            await window.navigator.clipboard.writeText(window.location.href);
        }
        if (typeof window.fathom !== 'undefined') {
            window.fathom.trackEvent('shared result');
        }
    }
</script>
<svelte:head>
    <!-- Fathom - beautiful, simple website analytics -->
    <script src="https://cdn.usefathom.com/script.js" data-site="JQIHBWMK" defer></script>
    <!-- / Fathom -->
</svelte:head>
<div class="p-2 md:p-4 lg:py-8 lg:px-0">
    <main class="max-w-5xl md:px-6 mx-auto bg-white rounded-lg">
        <div class="p-4 md:p-6 lg:p-8">
            <h1 class="font-bold md:text-4xl sm:text-5xl text-4xl text-gray-900 tracking-tight">PHPStan Drupal Extension Playground</h1>
            <p class="my-4">Try out PHPStan with phpstan-drupal and all of its features here in the editor. <a href="https://phpstan.org/" class="hover:no-underline underline">Learn more about PHPStan »</a></p>
            <form class="space-y-4" on:submit={analyse}>
                <div bind:this={editorMount}></div>
                {#if !editor}
                    <textarea bind:value={data.code} rows="10" name="code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-light-navy-blue sm:text-sm sm:leading-6"></textarea>
                {/if}
                <details class="border border-gray-300 rounded-md p-2">
                    <summary class="text-sm">Advanced options</summary>
                    <div class="flex flex-col items-center md:flex-row mt-4 space-x-6">
                        <label class="inline-flex items-center md:mt-0 mt-4">
                            <input type="checkbox" name="strictRules" bind:checked={data.strictRules} class="border-gray-300 rounded">
                            <span class="ml-2">Strict rules</span>
                        </label>
                        <label class="inline-flex items-center md:mt-0 mt-4">
                            <input type="checkbox" name="bleedingEdge" bind:checked={data.bleedingEdge} class="border-gray-300 rounded">
                            <span class="ml-2">Bleeding edge</span>
                        </label>
                        <label class="inline-flex items-center md:mt-0 mt-4">
                            <input type="checkbox" name="treatPhpDocTypesAsCertain" bind:checked={data.treatPhpDocTypesAsCertain} class="border-gray-300 rounded">
                            <span class="ml-2">Treat PHPDoc types as certain</span>
                        </label>
                    </div>
                </details>
                <div class="flex flex-col space-y-2 md:items-center md:flex-row mt-4 md:space-x-4 md:space-y-0">
                    <select name="level" bind:value={data.level} class="block border border-gray-300 md:mt-0 md:mx-0 mt-4 rounded-md">
                        <option value="0">Level 0</option>
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5</option>
                        <option value="6">Level 6</option>
                        <option value="7">Level 7</option>
                        <option value="8">Level 8</option>
                        <option value="9">Level 9</option>
                    </select>
                    <button disabled={processing} class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">Analyse</button>
                    <div class="hidden md:block flex-grow"></div>
                    <button on:click={share} type="button" class="bg-gray-100 border border-gray-300 flex-grow font-medium h-10 hover:bg-gray-200 inline-flex items-center justify-center leading-4 md:flex-grow-0 md:w-32 px-2.5 py-3 rounded-lg text-md w-auto">
                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="h-6 w-6"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                        <span class="ml-2">Share</span>
                    </button>
                </div>
            </form>
            {#if processing}
                <div class="m-auto w-12 h-12 mt-8">
                    <div role="status">
                        <svg aria-hidden="true" class="text-gray-200 animate-spin dark:text-gray-600 fill-blue-700" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                        </svg>
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            {/if}
            <div class="pt-4">
                {#each result?.tabs || [] as tab}
                    {#if tab.errors.length > 0}
                        <div class="flex items-stretch md:mx-0">
                            <span class="bg-red-100 flex-grow font-medium h-12 inline-flex items-center justify-center leading-4 md:flex-grow-0 mt-4 px-4 py-3 rounded-lg text-lg text-red-900" data-bind="text: errorsText">
                                Found {tab.errors.length} errors
                            </span>
                        </div>
                        <table class="mt-8 w-full">
                            <thead>
                                <tr>
                                    <th class="bg-gray-50 border-b border-gray-200 font-medium leading-4 px-6 py-3 text-base text-gray-500 text-left tracking-wider"> Line </th>
                                    <th class="bg-gray-50 border-b border-gray-200 font-medium leading-4 px-6 py-3 text-base text-gray-500 text-left tracking-wider"> Error </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                            {#each tab.errors as error}
                                <tr>
                                    <td class="border-b border-gray-200 font-medium leading-5 px-6 py-4 text-base text-gray-500">
                                        {error.line}
                                    </td>
                                    <td class="border-b border-gray-200 font-medium leading-5 px-6 py-4 text-base text-gray-500">
                                        <div class="flex flex-col md:flex-row md:items-center">
                                            <div class="flex-shrink">
                                                <div>{error.message}</div>
                                                {#if error.tip}
                                                    <div>{error.tip}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                            </tbody>
                        </table>
                    {/if}
                {/each}
            </div>
        </div>
    </main>
    <div class="mt-4 max-w-5xl md:px-6 mx-auto bg-white rounded-lg">
        <div class="p-4 md:p-6 lg:p-8">
            <h2 class="font-bold text-xl text-gray-900 tracking-tight">Sponsors</h2>
            <div class="flex flex-col space-y-2 md:items-center md:flex-row mt-4 md:space-x-4 md:space-y-0">
                <a href="https://www.undpaul.de/"><img src="https://www.undpaul.de/themes/custom/undpaul3/logo.svg" alt="undpaul" width="250" /></a>
                <a href="https://www.optasy.com/">
                    <svg id="Warstwa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="200" viewBox="0 0 160 60" enable-background="new 0 0 160 60" xml:space="preserve"><g> <path fill="#3A3A38" d="M38.761,49.624c-0.935,0-1.741-0.761-1.741-1.73v-23.75c0-0.97,0.695-1.729,1.63-1.729h10.283	c0.138,0,0.969-0.002,2.042,0.275c1.523,0.381,2.874,1.143,3.912,2.146c0.763,0.762,1.386,1.696,1.801,2.769	c0.45,1.178,0.692,2.528,0.692,4.018c0,3.288-1.385,5.781-3.981,7.271c-1.87,1.073-4.362,1.593-7.444,1.593	c-0.935,0-1.73-0.763-1.73-1.73c0-0.971,0.796-1.731,1.73-1.731c2.459,0,4.397-0.382,5.713-1.144	c1.523-0.866,2.251-2.216,2.251-4.258c0-1.386-0.277-2.528-0.762-3.395c-0.381-0.692-0.935-1.2-1.593-1.581	c-1.246-0.727-2.527-0.749-2.631-0.749h-8.43v21.996C40.503,48.863,39.73,49.624,38.761,49.624 M83.187,24.155	c0-0.969-0.763-1.741-1.73-1.741H62.794c-0.97,0-1.731,0.772-1.731,1.741c0,0.97,0.762,1.742,1.731,1.742h7.563v21.996	c0,0.97,0.772,1.73,1.742,1.73c0.936,0,1.742-0.761,1.742-1.73V25.897h7.614C82.424,25.897,83.187,25.125,83.187,24.155 M106.969,49.487c0.865-0.415,1.246-1.421,0.865-2.319L97.065,23.416c-0.242-0.589-0.899-1.005-1.558-1.005	c-0.692,0-1.281,0.382-1.593,1.005L82.976,47.168c-0.347,0.796-0.451,1.765,0.311,2.249c0.728,0.451,1.593,0.242,2.251-0.415	c1.315-1.28,3.115-2.7,4.847-3.773c1.696-1.039,3.428-1.697,5.193-2.044c2.182-0.484,4.432-0.45,6.682,0.104l2.39,5.332	C105.133,49.557,106.171,49.833,106.969,49.487 M100.701,39.792c-1.904-0.173-3.809-0.103-5.678,0.312	c-1.939,0.382-4.362,1.315-6.198,2.354l6.648-14.161L100.701,39.792z M122.499,50.179c2.284,0,8.378-1.073,9.417-6.232	c0.692-3.461-0.277-7.547-7.687-9.348c-5.781-1.419-8.31-2.285-8.31-5.193c0-3.15,3.082-4.188,5.782-4.188	c1.144,0,2.319,0.033,3.393,0.449c1.281,0.484,2.32,1.212,3.014,1.973c0.172,0.139,0.311,0.313,0.483,0.416	c0.623,0.346,1.386,0.104,1.801-0.416c0.381-0.449,0.52-1.073,0.312-1.627c-0.449-1.314-1.939-2.562-3.844-3.288	c-1.488-0.589-3.357-0.9-5.158-0.9c-2.389,0-6.474,0.484-8.586,4.708c-0.692,1.42-0.901,4.432,0.242,6.336	c0.935,1.593,2.596,2.597,4.259,3.255c1.558,0.658,3.219,1.074,4.882,1.421c2.492,0.519,5.436,1.385,6.231,3.738	c0.277,0.796,0.208,1.695-0.104,2.493c-0.623,1.592-2.181,2.423-3.809,2.77c-1.315,0.277-2.942,0.312-4.536,0.069	c-1.869-0.312-3.809-0.97-5.054-2.493c-0.208-0.277-0.416-0.589-0.728-0.762c-1.246-0.624-2.527,0.9-2.112,2.112	c0.415,1.178,1.42,2.251,2.943,3.047c1.211,0.692,2.77,1.177,4.432,1.453C120.663,50.11,121.599,50.179,122.499,50.179 M157.722,25.112c0.52-0.797,0.291-1.869-0.506-2.424c-0.796-0.519-1.891-0.312-2.41,0.485l-7.891,11.806l-7.855-11.806	c-0.52-0.797-1.584-1.004-2.381-0.485c-0.797,0.555-0.987,1.627-0.468,2.424l9.035,13.504v9.277c0,0.935,0.807,1.73,1.741,1.73	c0.97,0,1.741-0.796,1.741-1.73v-9.277L157.722,25.112"></path> <linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="16.1777" y1="50.1787" x2="16.1777" y2="9.8218"> <stop offset="0" style="stop-color:#6B4795"></stop> <stop offset="0.0384" style="stop-color:#654D99"></stop> <stop offset="0.3296" style="stop-color:#3E73B7"></stop> <stop offset="0.5966" style="stop-color:#218ECD"></stop> <stop offset="0.8288" style="stop-color:#109FDA"></stop> <stop offset="1" style="stop-color:#0AA5DF"></stop> </linearGradient> <path fill="url(#SVGID_1_)" d="M16.494,10.172l-0.316-0.351l-0.315,0.351c-0.348,0.386-5.489,6.143-9.41,12.917l9.726-7.164	c4.209,5.299,10.233,14.174,10.233,20.076c0,5.644-4.59,10.233-10.233,10.233c-5.643,0-10.233-4.59-10.233-10.233	c0-5.643,4.591-10.233,10.233-10.233c1.089,0,1.972-0.883,1.972-1.973c0-1.089-0.883-1.972-1.972-1.972	C8.348,21.823,2,28.171,2,36.001s6.348,14.178,14.178,14.178c7.818,0,14.178-6.36,14.178-14.178	C30.355,25.68,17.06,10.8,16.494,10.172z"></path></g></svg>
                </a>
            </div>
            <p class="pt-4 text-sm">
                <a href="https://github.com/sponsors/mglaman">Would you like to sponsor?</a>
            </p>
        </div>
    </div>
</div>
