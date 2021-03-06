import React from 'react';
import PropTypes from 'prop-types';
import * as App from '../config/app';
import _ from 'lodash';
import axios from 'axios';

class ComboComponent extends React.PureComponent {
    constructor(props) {
        super(props);
        this.state = {
            optionPageObject: {},
        };
    }

    componentDidMount() {
        this.initSelector();
        this.query();
    }

    componentDidUpdate() {
        this.updateSelector();
    }

    query(search) {
        const {modelClass} = this.props;

        let searchColumn = this.getSearchColumns();
        let eagerLoading = this.getEagerLoading();
        let pageSize = this.getPageSize();

        let instance = null;

        if (window.axios) {
            instance = window.axios;
        } else {
            instance = axios;
        }

        instance.get(`${App.APP_URL}/combosearch`, {
            params: {
                model_class: modelClass,
                pagination: {
                    page: 1,
                    perpage: pageSize
                },
                query: {
                    generalSearch: search,
                },
                search_columns: searchColumn,
                eager_loading: eagerLoading
            },
        }).then(response => {
            let optionPageObject = response.data;
            this.setState({
                optionPageObject: optionPageObject,
            });

            this.updateSelector();

        }).catch(error => {
            console.error(error);
        });
    }

    getSearchColumns() {
        const {defColumns} = this.props;
        let searchColumns = [];
        _.forEach(defColumns, (defColumn, index) => {
            if (defColumn.searchable) {
                searchColumns.push(defColumn.field);
            }
        });

        return searchColumns;
    }

    getEagerLoading() {
        const {defColumns} = this.props;

        let eagerLoading = [];
        let eagerColumns = null;
        _.forEach(defColumns, (defColumn, index) => {
            if ((defColumn.field).lastIndexOf('.') !== -1) {
                eagerColumns = _.split(defColumn.field, '.', 2);
                if (_.size(eagerColumns) > 1) {
                    eagerColumns = _.join(_.dropRight(eagerColumns), '.');
                    eagerLoading.push(eagerColumns);

                } else {
                    eagerLoading.push(eagerColumns);
                }
            }
        });

        return _.uniq(eagerLoading);
    }

    getPageSize() {
        const {size} = this.props;
        if (_.isNil(size) || size <= 0) {
            return 9;
        }
        return size;
    }

    getInitValue() {
        const {initValue} = this.props;
        if (_.isNil(initValue) || _.trim(initValue) === '') {
            return {};
        }
        return JSON.parse(initValue);
    }

    getIdKey() {
        const {idKey} = this.props;
        if (_.isNil(idKey)) {
            return 'id';
        }
        return idKey;
    }

    getOptionList() {
        const {optionPageObject} = this.state;
        const initValue = this.getInitValue();

        let options = [];
        if (!_.isEmpty(initValue)) {
            options.push(initValue);
        }

        if (!_.isEmpty(optionPageObject)) {
            options = options.concat(optionPageObject.data);
        }

        const idKey = this.getIdKey();
        return options.map((option, index) => {
            let optionText = this.getOptionText(option);

            return (
                <option key={index} value={option[idKey]}>
                    {optionText}
                </option>
            );
        });
    }

    getOptionText(option) {
        const {showColumns} = this.props;

        let optionText = null;
        let properties = null;
        let optionTextList = showColumns.map((key) => {
            if (key.indexOf('.') === -1) {
                optionText = !_.isNil(option[key]) ? option[key] : '';

            } else {
                optionText = option;
                properties = _.split(key, '.');

                for (let i = 0; i < properties.length; i++) {
                    optionText = optionText[properties[i]];
                }
            }

            return optionText;
        });

        return _.join(optionTextList, ' | ');
    }

    initSelector() {
        const {size, title, width, onChange} = this.props;
        const initValue = this.getInitValue();
        const idKey = this.getIdKey();

        let thisClass = this;
        $(this.selector).selectpicker({
            style: 'btn btn-primary btn-round',
            size: size,
            liveSearch: true,
            title: title,
            width: width,
        }).on('loaded.bs.select', (e) => {
            let searchBox = $(this.selector).parent().find('.bs-searchbox').find('input');
            searchBox.on('input', (e) => _.debounce(
                () => thisClass.query(e.target.value), 250
            ));
        }).on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
            let currentValue = e.target.value;
            if (isSelected && currentValue != previousValue) {
                if (onChange) {
                    onChange(currentValue, previousValue);
                }
            }
        });

        if (!_.isNil(initValue)) {
            console.log(initValue[idKey]);
            $(this.selector).selectpicker('val', initValue[idKey]);
            console.log($(this.selector).val());
        }
    }

    updateSelector() {
        $(this.selector).selectpicker('refresh');
    }

    render() {
        const {id, name} = this.props;

        let optionList = this.getOptionList();

        return (
            <select ref={(selector) => {
                this.selector = selector;
            }}
                    id={id}
                    name={name}
            >
                {optionList}
            </select>
        );
    }
}

ComboComponent.propTypes = {
    id: PropTypes.string,
    idKey: PropTypes.string.isRequired,
    modelClass: PropTypes.string.isRequired,
    name: PropTypes.string,
    title: PropTypes.string,
    size: PropTypes.number,
    showColumns: PropTypes.array.isRequired,
    defColumns: PropTypes.array.isRequired,
    initValue: PropTypes.string,
    width: PropTypes.string,
    onChange: PropTypes.func,
};

ComboComponent.defaultProps = {
    idKey: 'id',
    size: 9,
    initValue: '',
    width: '100%',
};

export default ComboComponent;