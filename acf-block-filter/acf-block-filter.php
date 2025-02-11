<?php 

/*
Plugin Name: ACF Block Filter
Description: A plugin to filter ACF field groups based on selected checkboxes.
Version: 1.0
Author: Barry Tickle
Author URI: https://barrytickle.com

*/

if (!defined('ABSPATH')) {
    exit;
}
add_action('admin_footer', function () {
    if (strpos($_SERVER['REQUEST_URI'], '/edit.php?post_type=acf-field-group') === false) {
        return;
    }
    ?>
    <script>

        const STATE = {
            filters: [],
        }

        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }

        function getCookie(name) {
            const cname = name + "=";
            const decodedCookie = decodeURIComponent(document.cookie);
            const ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(cname) == 0) {
                    return c.substring(cname.length, c.length);
                }
            }
            return "";
        }

        const gatherItems = (list = [])  => {
            const items = document.querySelectorAll('td.column-acf-location');

            items.forEach((item) => {
                const trim = item.textContent.trim().toLowerCase();
                const parsedItem = trim[0].toUpperCase() + trim.slice(1);

                if (!list.includes(parsedItem)) {
                    list.push(parsedItem);
                }
            });

            return list;
        }

        const generateCheckboxes = (items) => {
            let html = '';
            items.forEach((item) => {
                const label = item.toLowerCase().replace(' ', '-');
                html += `
                    <label for="${label}">
                        <input type="checkbox" name="acf-filter" value="${label}" id="${label}" />
                        ${item}
                    </label>
                `;
            });

            return html;
        }

        const toggleCheckboxes = () => {
            const {filters} = STATE;
            const checkBoxes = document.querySelectorAll('.acf-block-filter--checkboxes input[type="checkbox"]');

            checkBoxes.forEach((checkbox) => {
                const filter = filters.find((item) => item.name.toLowerCase() === checkbox.value);
                if(!!!filter) return;
                checkbox.checked = filter.checked;
                checkbox.dispatchEvent(new Event('change'));
            });
        }

        const insertCheckboxes = (list) => {
            const html = generateCheckboxes(list);

            const anchor = document.querySelector('#posts-filter')?.previousElementSibling;
            if(!!!anchor) return;

            anchor.insertAdjacentHTML('beforebegin', `
                <div class="container acf-block-filter--checkboxes" style="display:flex; flex-direction:column;">
                    <h2>Filter ACF Field Groups</h2>
                    <div style="display:flex; gap:10px">
                        ${html}
                    </div>
                </div>
            `);
        }

        const bindCheckboxes = () => {
            const checkBoxes = document.querySelectorAll('.acf-block-filter--checkboxes input[type="checkbox"]');
            checkBoxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const value = checkbox.value;
                    const filter = STATE.filters.find((item) => item.name.toLowerCase() === value);
                    console.log('filter', filter);
                    filter.checked = checkbox.checked;
                    setCookie('acf-block-filter', JSON.stringify({filters: STATE.filters}), 1);
                    showHideRows();
                });
            })
        }

        const gatherData = () => {
            const data = getCookie('acf-block-filter');
            if(!!!data){
                console.log(data);
                return [];
            }
            if(data){
                return JSON.parse(data).filters;
            } 
        }

        const updateState = (data, empty = false) => {
            if(empty){
                data.forEach((item) => {
                    STATE.filters.push({
                        name: item,
                        checked: true,
                    });
                });   
            } else {
                STATE.filters = data;
            }
        }

        const showHideRows = () =>{ 
            const rows = document.querySelectorAll('tr.type-acf-field-group');
            rows.forEach((row) => {
                const column = row.querySelector('td.column-acf-location');
                if(!!!column) return;
                const title = column.textContent.trim().toLowerCase();
                const filter = STATE.filters.find((item) => item.name.toLowerCase() === title);
                if(!!!filter) return;
                row.style.display = filter.checked ? 'table-row' : 'none';
            });
        }
        

        (() => {
            const list = gatherItems();
            insertCheckboxes(list);
            
            const data = gatherData();

            if(data.length == 0){
                updateState(list, true);
            } else{
                console.log('fill');
                updateState(data);
            }

            toggleCheckboxes();
            bindCheckboxes();

            if(data.length > 0){
                showHideRows();
            }
        })();
    </script>
    <?php
});