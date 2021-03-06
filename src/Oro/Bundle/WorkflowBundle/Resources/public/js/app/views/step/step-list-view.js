define(function(require) {
    'use strict';

    var StepsListView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var StepRowView = require('./step-row-view');

    StepsListView = BaseView.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            workflow: null
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#step-list-template').html();
            this.template = _.template(template);
            this.rowViews = [];

            this.$listEl = $(this.template());
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);
            this.$el.html(this.$listEl);

            this.listenTo(this.getCollection(), 'change', this.render);
            this.listenTo(this.getCollection(), 'add', this.render);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);
        },

        addItem: function(item) {
            var rowView = new StepRowView({
                model: item,
                workflow: this.options.workflow
            });
            this.rowViews.push(rowView);
            this.$listElBody.append(rowView.render().$el);
        },

        addAllItems: function(collection) {
            this.resetView();
            collection.each(_.bind(this.addItem, this));
        },

        getCollection: function() {
            return this.options.workflow.get('steps');
        },

        remove: function() {
            this.resetView();
            StepsListView.__super__.remove.call(this);
        },

        resetView: function() {
            _.each(this.rowViews, function(rowView) {
                rowView.remove();
            });
            this.rowViews = [];
        },

        render: function() {
            this.getCollection().sort();
            this.addAllItems(this.getCollection());
            return this;
        }
    });

    return StepsListView;
});
