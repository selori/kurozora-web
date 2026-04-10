/*
 * ATTENTION: An "eval-source-map" devtool has been used.
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file with attached SourceMaps in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/js/db"],{

/***/ "./resources/js/db.js"
/*!****************************!*\
  !*** ./resources/js/db.js ***!
  \****************************/
() {

eval("{(function () {\n  initializeLibraryDatabase();\n  function initializeLibraryDatabase() {\n    // Open or create the IndexedDB database\n    var request = indexedDB.open('library', 1);\n\n    // Handle database upgrade (create object store)\n    request.onupgradeneeded = function (event) {\n      var libraryDB = event.target.result;\n      libraryDB.createObjectStore('libraryData', {\n        keyPath: 'id'\n      });\n    };\n\n    // Handle successful database opening\n    request.onsuccess = function (event) {\n      window.libraryDB = event.target.result;\n      window.dispatchEvent(new Event('librarydbloaded'));\n    }.bind(this);\n\n    // Handle errors\n    request.onerror = function (event) {\n      console.error('Error opening IndexedDB for library database:', event.target.error);\n    };\n  }\n})();//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9yZXNvdXJjZXMvanMvZGIuanMiLCJuYW1lcyI6WyJpbml0aWFsaXplTGlicmFyeURhdGFiYXNlIiwicmVxdWVzdCIsImluZGV4ZWREQiIsIm9wZW4iLCJvbnVwZ3JhZGVuZWVkZWQiLCJldmVudCIsImxpYnJhcnlEQiIsInRhcmdldCIsInJlc3VsdCIsImNyZWF0ZU9iamVjdFN0b3JlIiwia2V5UGF0aCIsIm9uc3VjY2VzcyIsIndpbmRvdyIsImRpc3BhdGNoRXZlbnQiLCJFdmVudCIsImJpbmQiLCJvbmVycm9yIiwiY29uc29sZSIsImVycm9yIl0sInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9yZXNvdXJjZXMvanMvZGIuanM/YWE3OSJdLCJzb3VyY2VzQ29udGVudCI6WyIoZnVuY3Rpb24oKSB7XG4gICAgaW5pdGlhbGl6ZUxpYnJhcnlEYXRhYmFzZSgpXG5cbiAgICBmdW5jdGlvbiBpbml0aWFsaXplTGlicmFyeURhdGFiYXNlKCkge1xuICAgICAgICAvLyBPcGVuIG9yIGNyZWF0ZSB0aGUgSW5kZXhlZERCIGRhdGFiYXNlXG4gICAgICAgIGxldCByZXF1ZXN0ID0gaW5kZXhlZERCLm9wZW4oJ2xpYnJhcnknLCAxKVxuXG4gICAgICAgIC8vIEhhbmRsZSBkYXRhYmFzZSB1cGdyYWRlIChjcmVhdGUgb2JqZWN0IHN0b3JlKVxuICAgICAgICByZXF1ZXN0Lm9udXBncmFkZW5lZWRlZCA9IGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgICAgICAgICBsZXQgbGlicmFyeURCID0gZXZlbnQudGFyZ2V0LnJlc3VsdFxuICAgICAgICAgICAgbGlicmFyeURCLmNyZWF0ZU9iamVjdFN0b3JlKCdsaWJyYXJ5RGF0YScsIHsga2V5UGF0aDogJ2lkJyB9KVxuICAgICAgICB9XG5cbiAgICAgICAgLy8gSGFuZGxlIHN1Y2Nlc3NmdWwgZGF0YWJhc2Ugb3BlbmluZ1xuICAgICAgICByZXF1ZXN0Lm9uc3VjY2VzcyA9IGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgICAgICAgICB3aW5kb3cubGlicmFyeURCID0gZXZlbnQudGFyZ2V0LnJlc3VsdFxuICAgICAgICAgICAgd2luZG93LmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdsaWJyYXJ5ZGJsb2FkZWQnKSlcbiAgICAgICAgfS5iaW5kKHRoaXMpXG5cbiAgICAgICAgLy8gSGFuZGxlIGVycm9yc1xuICAgICAgICByZXF1ZXN0Lm9uZXJyb3IgPSBmdW5jdGlvbihldmVudCkge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcignRXJyb3Igb3BlbmluZyBJbmRleGVkREIgZm9yIGxpYnJhcnkgZGF0YWJhc2U6JywgZXZlbnQudGFyZ2V0LmVycm9yKVxuICAgICAgICB9XG4gICAgfVxufSkoKVxuIl0sIm1hcHBpbmdzIjoiQUFBQSxDQUFDLFlBQVc7RUFDUkEseUJBQXlCLENBQUMsQ0FBQztFQUUzQixTQUFTQSx5QkFBeUJBLENBQUEsRUFBRztJQUNqQztJQUNBLElBQUlDLE9BQU8sR0FBR0MsU0FBUyxDQUFDQyxJQUFJLENBQUMsU0FBUyxFQUFFLENBQUMsQ0FBQzs7SUFFMUM7SUFDQUYsT0FBTyxDQUFDRyxlQUFlLEdBQUcsVUFBU0MsS0FBSyxFQUFFO01BQ3RDLElBQUlDLFNBQVMsR0FBR0QsS0FBSyxDQUFDRSxNQUFNLENBQUNDLE1BQU07TUFDbkNGLFNBQVMsQ0FBQ0csaUJBQWlCLENBQUMsYUFBYSxFQUFFO1FBQUVDLE9BQU8sRUFBRTtNQUFLLENBQUMsQ0FBQztJQUNqRSxDQUFDOztJQUVEO0lBQ0FULE9BQU8sQ0FBQ1UsU0FBUyxHQUFHLFVBQVNOLEtBQUssRUFBRTtNQUNoQ08sTUFBTSxDQUFDTixTQUFTLEdBQUdELEtBQUssQ0FBQ0UsTUFBTSxDQUFDQyxNQUFNO01BQ3RDSSxNQUFNLENBQUNDLGFBQWEsQ0FBQyxJQUFJQyxLQUFLLENBQUMsaUJBQWlCLENBQUMsQ0FBQztJQUN0RCxDQUFDLENBQUNDLElBQUksQ0FBQyxJQUFJLENBQUM7O0lBRVo7SUFDQWQsT0FBTyxDQUFDZSxPQUFPLEdBQUcsVUFBU1gsS0FBSyxFQUFFO01BQzlCWSxPQUFPLENBQUNDLEtBQUssQ0FBQywrQ0FBK0MsRUFBRWIsS0FBSyxDQUFDRSxNQUFNLENBQUNXLEtBQUssQ0FBQztJQUN0RixDQUFDO0VBQ0w7QUFDSixDQUFDLEVBQUUsQ0FBQyIsImlnbm9yZUxpc3QiOltdfQ==\n//# sourceURL=webpack-internal:///./resources/js/db.js\n\n}");

/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./resources/js/db.js"));
/******/ }
]);