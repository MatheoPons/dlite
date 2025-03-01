/*
 * d.Lite - low-code platform for local-first Web/Mobile development
 * Copyright (C) 2022 CINCHEO
 *                    https://www.cincheo.com
 *                    renaud.pawlak@cincheo.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

Vue.component('apps-panel', {
    template: `
        <div class="d-flex flex-row flex-wrap" style="justify-content: center; gap: 10px">
          <b-card v-for="(app, i) in apps" size="sm"
            :key="i"
            :img-src="app.icon"
            :img-alt="app.name"
            img-top
            class="mb-2 mt-2 app-card"
          >
          <template #header>
            <h6 class="mb-0">{{ app.name }}<b-button variant="primary" @click="open(app)" size="sm" pill class="float-right">Open</b-button></h6>
          </template>
            <b-card-text>
              {{ app.description }}
            </b-card-text>
          </b-card>            
        </div>
    `,
    props: ['apps', 'basePath'],
    methods: {
        open: function (app) {
            const url = this.basePath + (this.basePath.indexOf('?') > -1 ? '&' : '?') + 'src=' + app.url;
            window.location = url;
        }
    }
});
