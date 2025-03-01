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

Vue.component('iterator-view', {
    extends: editableComponent,
    template: `
        <div :id="cid">
            <component-icon v-if="edit" :type="viewModel.type"></component-icon>
            <component-badge :component="getThis()" :edit="edit" :targeted="targeted" :selected="selected"></component-badge>
            <div 
                :style="containerStyle()"
                :class="containerClass()"             
            >
                <component-view v-for="(item, index) in currentPageItems" :key="index" 
                    :cid="viewModel.body ? viewModel.body.cid : undefined" 
                    keyInParent="body" 
                    :iteratorIndex="currentPageFirstIndex() + index" 
                    :inSelection="isEditable()"
                />
                <component-view v-if="edit && (!Array.isArray(value) || value.length == 0)" 
                    :cid="viewModel.body ? viewModel.body.cid : undefined" 
                    keyInParent="body" 
                    :inSelection="isEditable()" 
                    :iteratorIndex="0"
                />
            </div>
        </div>
    `,
    data: function () {
        return {
            currentPage: 1,
            currentPageItems: []
        }
    },
    watch: {
        currentPage: function () {
            this.updatePageItems();
            this.$emit("@page-changed", this.currentPage);
        },
        'viewModel.perPage': function () {
            this.updatePageItems();
        },
        value: function () {
            this.updatePageItems();
        }
    },
    methods: {
        containerStyle() {
            let style = 'display: flex; overflow: ' + (this.$eval(this.viewModel.scrollable, false) ? 'auto' : 'visible') + '; flex-direction: ' + (this.$eval(this.viewModel.direction, false) ? this.$eval(this.viewModel.direction) : 'column');
            if (this.viewModel.wrap) {
                style += '; flex-wrap: ' + this.$eval(this.viewModel.wrap, '');
            }
            if (this.viewModel.justify) {
                style += '; justify-content: ' + this.toFlexStyle(this.$eval(this.viewModel.justify, ''));
            }
            if (this.viewModel.alignItems) {
                style += '; align-items: ' + this.toFlexStyle(this.$eval(this.viewModel.alignItems, ''));
            }
            if (this.viewModel.alignContent) {
                style += '; align-content: ' + this.toFlexStyle(this.$eval(this.viewModel.alignContent, ''));
            }
            if (this.viewModel.rowGap) {
                style += '; row-gap: ' + this.$eval(this.viewModel.rowGap, '');
            }
            if (this.viewModel.columnGap) {
                style += '; column-gap: ' + this.$eval(this.viewModel.columnGap, '');
            }
            if (this.viewModel.backgroundImage) {
                style += '; background-image: ' + this.$eval(this.viewModel.backgroundImage);
                style += '; background-size: ' + this.$evalWithDefault(this.viewModel.backgroundSize, 'cover');
                style += '; background-position: ' + this.$evalWithDefault(this.viewModel.backgroundPosition, 'center');
            }
            if (this.viewModel.style) {
                style += '; ' + this.$eval(this.viewModel.style, '');
            }
            return style;
        },
        containerClass() {
            let containerClass = this.componentClass();
            if (this.viewModel.fillHeight) {
                containerClass += ' overflow-auto';
            }
            if (!this.viewModel.disableContainerPadding) {
                containerClass += this.viewModel.fixedWidth ? ' container' : ' container-fluid';
            }
            return containerClass;
        },
        currentPageFirstIndex() {
            const perPage = this.$eval(this.viewModel.perPage);
            if (!perPage || perPage === '0' || perPage === '') {
                return 0;
            } else {
                return (this.currentPage - 1) * perPage;
            }
        },
        updatePageItems() {
            const perPage = this.$eval(this.viewModel.perPage);
            if (this.value === undefined) {
                this.currentPageItems = [];
                return;
            } else {
                if (!perPage || perPage === '0' || perPage === '') {
                    this.currentPageItems = this.value;
                } else {
                    this.currentPageItems = this.value.slice((this.currentPage - 1) * perPage, this.currentPage * perPage);
                }
            }
        },
        customEventNames() {
            return ["@page-changed"];
        },
        setCurrentPage(page) {
            this.currentPage = page;
        },
        customActionNames() {
            return [{value:'setCurrentPage',text:'setCurrentPage(pageNumber)'}];
        },
        propNames() {
            return [
                "cid",
                "class",
                "style",
                "dataSource",
                "field",
                "body",
                "perPage",
                "fillHeight",
                "disableContainerPadding",
                "fixedWidth",
                "direction",
                "wrap",
                "rowGap",
                "columnGap",
                "justify",
                "alignItems",
                "alignContent",
                "eventHandlers"
            ];
        },
        customPropDescriptors() {
            return {
                body: {
                    type: 'ref',
                    editable: true
                },
                fillHeight: {
                    type: 'checkbox',
                    description: "Stretch vertically to fill the parent component height",
                    literalOnly: true
                },
                disableContainerPadding: {
                    type: 'checkbox',
                    label: 'No padding',
                    literalOnly: true,
                    description: "Disable container padding",
                },
                fixedWidth: {
                    type: 'checkbox',
                    hidden: viewModel => viewModel.disableContainerPadding,
                    literalOnly: true,
                    description: "Enable fixed-width padding (full-width is the default - aka fluid)",
                },
                direction: {
                    type: 'select',
                    options: ['column', 'column-reverse', 'row', 'row-reverse'],
                    docLink: 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/#flex-direction'
                },
                wrap: {
                    type: 'select',
                    options: ['nowrap', 'wrap', 'wrap-reverse'],
                    docLink: 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/#flex-wrap'
                },
                justify: {
                    type: 'select',
                    options: ['start', 'end', 'center', 'space-between', 'space-around', 'space-evenly'],
                    docLink: 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/#justify-content'
                },
                alignItems: {
                    type: 'select',
                    options: ['stretch', 'start', 'end', 'center', 'baseline'],
                    docLink: 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/#align-items'
                },
                alignContent: {
                    type: 'select',
                    options: ['normal', 'stretch', 'start', 'end', 'center', 'space-between', 'space-around'],
                    docLink: 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/#align-content'
                }
            }
        }

    }
});


