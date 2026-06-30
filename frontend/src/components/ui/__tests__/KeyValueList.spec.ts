import { mount } from '@vue/test-utils'
import KeyValueList from '../KeyValueList.vue'

describe('KeyValueList', () => {
  it('renders label and value pairs', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [
          { label: 'Name', value: 'Laravel' },
          { label: 'Hours', value: 120 },
        ],
      },
    })

    expect(wrapper.text()).toContain('Name')
    expect(wrapper.text()).toContain('Laravel')
    expect(wrapper.text()).toContain('Hours')
    expect(wrapper.text()).toContain('120')
  })

  it('shows dash for null values', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [{ label: 'Empty', value: null }],
      },
    })

    expect(wrapper.text()).toContain('—')
  })

  it('shows dash for empty string values', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [{ label: 'Empty', value: '' }],
      },
    })

    expect(wrapper.text()).toContain('—')
  })

  it('hides items with hideWhenEmpty and null value', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [
          { label: 'Visible', value: 'yes' },
          { label: 'Hidden', value: null, hideWhenEmpty: true },
        ],
      },
    })

    expect(wrapper.text()).toContain('Visible')
    expect(wrapper.text()).toContain('yes')
    expect(wrapper.text()).not.toContain('Hidden')
  })

  it('shows items with hideWhenEmpty when value is present', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [{ label: 'Shown', value: 'data', hideWhenEmpty: true }],
      },
    })

    expect(wrapper.text()).toContain('Shown')
    expect(wrapper.text()).toContain('data')
  })

  it('renders empty state when no items', () => {
    const wrapper = mount(KeyValueList, {
      props: { items: [] },
    })

    expect(wrapper.find('dl').exists()).toBe(true)
  })

  it('renders default slot when items are empty', () => {
    const wrapper = mount(KeyValueList, {
      slots: { default: '<span>No data available</span>' },
    })

    expect(wrapper.text()).toContain('No data available')
  })

  it('applies row layout class', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [{ label: 'Test', value: 'val' }],
        layout: 'row',
      },
    })

    expect(wrapper.find('.key-value-list--row').exists()).toBe(true)
  })

  it('applies stack layout class', () => {
    const wrapper = mount(KeyValueList, {
      props: {
        items: [{ label: 'Test', value: 'val' }],
        layout: 'stack',
      },
    })

    expect(wrapper.find('.key-value-list--stack').exists()).toBe(true)
  })
})
